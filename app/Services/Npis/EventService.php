<?php

namespace App\Services\Npis;

use App\Contracts\NpisCloudStorage;
use App\Exceptions\NpisApiException;
use App\Models\Npis\AdminUser;
use App\Models\Npis\Faq;
use App\Models\Npis\OrganizerAccess;
use App\Models\Npis\ProtocolCategory;
use App\Models\Npis\ProtocolZone;
use App\Models\Npis\SummitEvent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EventService
{
    public const CACHE_EVENT = 'npis:event';

    public const CACHE_FAQS = 'npis:faqs';

    public const CACHE_PROTOCOL = 'npis:protocol';

    public function __construct(
        private readonly AuditService $audit,
        private readonly NpisCloudStorage $cloud,
    ) {}

    public function current(): SummitEvent
    {
        $event = SummitEvent::query()->find(config('npis.event_id'));

        if (! $event) {
            throw NpisApiException::make(503, 'EVENT_NOT_CONFIGURED', 'The summit has not been configured yet.');
        }

        return $event;
    }

    /**
     * @return array<string, mixed>
     */
    public function publicEvent(): array
    {
        return Cache::remember(self::CACHE_EVENT, $this->ttl(), fn (): array => $this->eventContract($this->current()));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function publishedFaqs(): array
    {
        return Cache::remember(self::CACHE_FAQS, $this->ttl(), fn (): array => Faq::query()
            ->where('event_id', config('npis.event_id'))
            ->where('published', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Faq $faq): array => $this->faqContract($faq))
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function protocol(): array
    {
        return Cache::remember(self::CACHE_PROTOCOL, $this->ttl(), function (): array {
            $zones = ProtocolZone::query()->orderBy('sort_order')->get();
            $categories = ProtocolCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return [
                'status' => config('npis.protocol.status', 'proposed'),
                'zones' => $zones->map(fn (ProtocolZone $zone): array => [
                    'code' => $zone->code,
                    'name' => $zone->name,
                ])->all(),
                'categories' => $categories->map(fn (ProtocolCategory $category): array => $category->toContract())->all(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function eventContract(SummitEvent $event): array
    {
        return [
            'eventId' => $event->id,
            'name' => $event->name,
            'startDate' => $event->start_date->format('Y-m-d'),
            'endDate' => $event->end_date->format('Y-m-d'),
            'timezone' => $event->timezone,
            'venue' => [
                'name' => $event->venue_name,
                'address' => $event->venue_address,
            ],
            'welcomeMessage' => [
                'title' => $event->welcome_message_title,
                'author' => $event->welcome_message_author,
                'authorTitle' => $event->welcome_message_author_title,
                'paragraphs' => $event->welcome_message_paragraphs ?? [],
                'approvedAt' => $event->welcome_message_approved_at?->toIso8601String(),
            ],
            'brochure' => [
                'title' => $event->brochure_title,
                'fileName' => $event->brochure_file_name,
                'url' => $event->brochure_url,
                'sizeBytes' => $event->brochure_size_bytes,
                'version' => $event->brochure_version,
                'publishedAt' => $event->brochure_published_at?->toIso8601String(),
            ],
            'deadlines' => $event->deadlines,
            'policies' => $event->policies,
        ];
    }

    /**
     * Full settings payload for the organizer "Event settings" screen.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $access = OrganizerAccess::current();
        $updatedBy = $access->updated_by
            ? AdminUser::query()->find($access->updated_by)
            : null;

        return [
            ...$this->eventContract($this->current()),
            'organizerAccess' => [
                'updatedAt' => $access->updated_at?->toIso8601String(),
                'updatedBy' => $updatedBy->name ?? 'System',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function faqContract(Faq $faq): array
    {
        return [
            'faqId' => $faq->id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'category' => $faq->category,
            'order' => $faq->sort_order,
            'published' => (bool) $faq->published,
            'updatedAt' => $faq->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(array $payload, AdminUser $actor, ?UploadedFile $brochureFile = null): SummitEvent
    {
        $event = $this->current();

        return DB::transaction(function () use ($event, $payload, $actor, $brochureFile): SummitEvent {
            unset($payload['brochureFile']);

            $event = SummitEvent::query()->lockForUpdate()->findOrFail($event->id);

            $mapped = [
                'name' => 'name',
                'startDate' => 'start_date',
                'endDate' => 'end_date',
                'timezone' => 'timezone',
            ];

            foreach ($mapped as $input => $column) {
                if (array_key_exists($input, $payload)) {
                    $event->{$column} = $payload[$input];
                }
            }

            if (array_key_exists('venue', $payload)) {
                $event->venue_name = $payload['venue']['name'] ?? $event->venue_name;
                $event->venue_address = $payload['venue']['address'] ?? $event->venue_address;
            }

            foreach (['theme'] as $field) {
                if (array_key_exists($field, $payload)) {
                    $event->{$field} = $payload[$field];
                }
            }

            if (array_key_exists('welcomeMessage', $payload)) {
                $welcome = $payload['welcomeMessage'];
                $event->welcome_message_title = $welcome['title'] ?? $event->welcome_message_title;
                $event->welcome_message_author = $welcome['author'] ?? $event->welcome_message_author;
                $event->welcome_message_author_title = $welcome['authorTitle'] ?? $event->welcome_message_author_title;
                $event->welcome_message_paragraphs = $welcome['paragraphs'] ?? $event->welcome_message_paragraphs;
                $event->welcome_message_approved_at = array_key_exists('approvedAt', $welcome)
                    ? $welcome['approvedAt']
                    : $event->welcome_message_approved_at;
            }

            if (array_key_exists('brochure', $payload)) {
                $brochure = $payload['brochure'];
                $event->brochure_title = $brochure['title'] ?? $event->brochure_title;
                $event->brochure_file_name = $brochure['fileName'] ?? $event->brochure_file_name;
                $event->brochure_url = $brochure['url'] ?? $event->brochure_url;
                $event->brochure_size_bytes = $brochure['sizeBytes'] ?? $event->brochure_size_bytes;
                $event->brochure_version = $brochure['version'] ?? $event->brochure_version;
                $event->brochure_published_at = $brochure['publishedAt'] ?? $event->brochure_published_at;
            }

            if ($brochureFile instanceof UploadedFile) {
                if ($event->brochure_public_id) {
                    $this->cloud->delete($event->brochure_public_id);
                }

                $upload = $this->cloud->uploadFile(
                    $brochureFile,
                    config('npis.storage.folders.brochures', 'npis/brochures'),
                );

                $event->brochure_file_name = 'NPIS-brochure-'.now()->format('YmdHis').'.pdf';
                $event->brochure_path = null;
                $event->brochure_url = $upload['url'];
                $event->brochure_public_id = $upload['public_id'];
                $event->brochure_size_bytes = $brochureFile->getSize();
                $event->brochure_version = $this->nextBrochureVersion($event->brochure_version);
                $event->brochure_published_at = now();
            }

            $deadlines = $event->deadlines ?? [];
            foreach (($payload['deadlines'] ?? []) as $key => $value) {
                $deadlines[$key] = $value;
            }
            $event->deadlines = $deadlines;

            $policies = $event->policies ?? [];
            foreach (($payload['policies'] ?? []) as $key => $value) {
                if ($key === 'verification' && is_array($value)) {
                    $policies['verification'] = array_merge($policies['verification'] ?? [], $value);
                } else {
                    $policies[$key] = $value;
                }
            }
            $event->policies = $policies;

            $event->save();

            $this->audit->record(
                $actor,
                'event.updated',
                'event_settings',
                $event->id,
                'Updated event settings',
                ['changed' => array_keys($payload)],
                request(),
            );

            $this->forgetPublicCaches();

            return $event->fresh();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<Faq>
     */
    public function replaceFaqs(array $items, AdminUser $actor): array
    {
        $event = $this->current();

        return DB::transaction(function () use ($event, $items, $actor): array {
            $existing = Faq::query()->where('event_id', $event->id)->get()->keyBy('id');
            $keptIds = [];

            foreach ($items as $index => $item) {
                $faq = isset($item['faqId']) ? $existing->get($item['faqId']) : null;
                $attributes = [
                    'question' => trim((string) $item['question']),
                    'answer' => trim((string) $item['answer']),
                    'category' => (string) $item['category'],
                    'published' => (bool) ($item['published'] ?? false),
                    'sort_order' => $index + 1,
                ];

                if ($faq) {
                    $faq->fill($attributes)->save();
                } else {
                    $faq = Faq::create([
                        'id' => 'faq_'.strtolower((string) str()->ulid()),
                        'event_id' => $event->id,
                        ...$attributes,
                    ]);
                }

                $keptIds[] = $faq->id;
            }

            $removed = Faq::query()
                ->where('event_id', $event->id)
                ->whereNotIn('id', $keptIds)
                ->delete();

            $result = Faq::query()
                ->where('event_id', $event->id)
                ->orderBy('sort_order')
                ->get()
                ->all();

            $this->audit->record(
                $actor,
                'faq.updated',
                'faq',
                'all',
                sprintf(
                    'Updated FAQs (%d total, %d published%s)',
                    count($result),
                    collect($result)->where('published', true)->count(),
                    $removed ? ", {$removed} removed" : ''
                ),
                ['count' => count($result), 'removed' => $removed],
                request(),
            );

            $this->forgetPublicCaches();

            return $result;
        });
    }

    public function forgetPublicCaches(): void
    {
        Cache::forget(self::CACHE_EVENT);
        Cache::forget(self::CACHE_FAQS);
        Cache::forget(self::CACHE_PROTOCOL);
        Cache::forget(ReferenceDataService::CACHE_RANKS);
        Cache::forget(ReferenceDataService::CACHE_STATES);
        Cache::forget(ReferenceDataService::CACHE_AIRPORTS);
        Cache::forget(ReferenceDataService::CACHE_BANKS);
    }

    private function ttl(): int
    {
        return min(300, max(1, (int) config('npis.cache_ttl', 300)));
    }

    private function nextBrochureVersion(?string $current): string
    {
        if ($current === null || ! is_numeric($current)) {
            return '1.0';
        }

        return number_format((float) $current + 0.1, 1, '.', '');
    }
}

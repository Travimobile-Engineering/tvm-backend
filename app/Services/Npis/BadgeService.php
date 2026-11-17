<?php

namespace App\Services\Npis;

use App\Enum\Npis\BadgeCategorySource;
use App\Enum\Npis\ParticipantType;
use App\Models\Npis\AdminUser;
use App\Models\Npis\Badge;
use App\Models\Npis\ProtocolCategory;
use App\Models\Npis\Registration;

class BadgeService
{
    public const VENDOR_CATEGORY_MAP = [
        'usher' => 'OC',
        'security' => 'SC',
        'photographer' => 'MD',
        'videographer' => 'MD',
        'journalist' => 'MD',
        'caterer' => 'SV',
        'cleaner' => 'SV',
        'driver' => 'SV',
        'decorator' => 'SV',
        'other' => 'SV',
    ];

    public function derivedCode(Registration $registration): string
    {
        if ($registration->participant_type === ParticipantType::Vendor) {
            return self::VENDOR_CATEGORY_MAP[$registration->profile?->vendor_service] ?? 'SV';
        }

        return 'DL';
    }

    public function effectiveCode(Registration $registration): string
    {
        return $registration->protocol_category
            ?? $registration->badge?->category
            ?? $this->derivedCode($registration);
    }

    public function category(Registration $registration): ProtocolCategory
    {
        $code = $this->effectiveCode($registration);

        return ProtocolCategory::query()->find($code)
            ?? ProtocolCategory::query()->findOrFail($this->derivedCode($registration));
    }

    public function ensureBadge(Registration $registration): Badge
    {
        $derived = $this->derivedCode($registration);

        $badge = Badge::query()->firstOrCreate(
            ['registration_id' => $registration->id],
            [
                'category' => $derived,
                'source' => BadgeCategorySource::Derived->value,
            ]
        );

        if (! $badge->category) {
            $badge->category = $derived;
            $badge->source = BadgeCategorySource::Derived->value;
            $badge->save();
        }

        $registration->setRelation('badge', $badge);

        return $badge;
    }

    public function assign(Registration $registration, ?string $category, AdminUser $actor): Badge
    {
        $badge = $this->ensureBadge($registration);
        $derived = $this->derivedCode($registration);
        $before = $this->effectiveCode($registration);

        if ($category === null || $category === $derived) {
            $registration->protocol_category = null;
            $badge->category = $derived;
            $badge->source = BadgeCategorySource::Derived->value;
        } else {
            $registration->protocol_category = $category;
            $badge->category = $category;
            $badge->source = BadgeCategorySource::Assigned->value;
        }

        $registration->save();
        $badge->save();

        return $badge;
    }

    /**
     * @return array<string, mixed>
     */
    public function contract(Registration $registration): array
    {
        $badge = $this->ensureBadge($registration);
        $category = $this->category($registration);
        $photoUrl = $badge->photo_url
            ? app(MediaService::class)->photoUrl($registration, $badge)
            : null;

        return [
            'category' => $category->toContract(),
            'source' => $registration->protocol_category ? 'assigned' : 'derived',
            'photo' => $badge->photo_url ? [
                'url' => $photoUrl,
                'source' => $badge->photo_source,
                'uploadedAt' => $badge->photo_uploaded_at?->toIso8601String(),
            ] : null,
            'photoRequired' => (bool) $category->photo_required,
            'photoMissing' => (bool) $category->photo_required && ! $badge->photo_url,
        ];
    }
}

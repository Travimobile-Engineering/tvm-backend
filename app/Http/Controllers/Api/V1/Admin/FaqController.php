<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\NpisApiController;
use App\Http\Requests\Npis\ReplaceFaqsRequest;
use App\Models\Npis\Faq;
use App\Services\Npis\EventService;
use Illuminate\Http\JsonResponse;

class FaqController extends NpisApiController
{
    public function __construct(private readonly EventService $events) {}

    public function index(): JsonResponse
    {
        $faqs = Faq::query()
            ->where('event_id', config('npis.event_id'))
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Faq $faq): array => $this->events->faqContract($faq))
            ->all();

        return $this->success($faqs, 'FAQs retrieved successfully');
    }

    public function update(ReplaceFaqsRequest $request): JsonResponse
    {
        $faqs = $this->events->replaceFaqs($request->validated('items'), $this->admin($request));

        return $this->success(
            array_map($this->events->faqContract(...), $faqs),
            'FAQs updated successfully'
        );
    }
}

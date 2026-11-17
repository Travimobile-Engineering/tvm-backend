<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Npis\EventService;
use Illuminate\Http\JsonResponse;

class EventController extends NpisApiController
{
    public function __construct(private readonly EventService $events) {}

    public function show(): JsonResponse
    {
        return $this->success($this->events->publicEvent(), 'Event retrieved successfully');
    }

    public function faqs(): JsonResponse
    {
        return $this->success($this->events->publishedFaqs(), 'FAQs retrieved successfully');
    }
}

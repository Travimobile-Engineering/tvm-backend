<?php

namespace App\Http\Requests\Npis;

use App\Services\Npis\EventService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'startDate' => ['sometimes', 'date_format:Y-m-d'],
            'endDate' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'venue' => ['sometimes', 'array'],
            'venue.name' => ['sometimes', 'string', 'max:200'],
            'venue.address' => ['sometimes', 'string', 'max:200'],
            'welcomeMessage' => ['sometimes', 'array'],
            'welcomeMessage.title' => ['sometimes', 'string', 'max:200'],
            'welcomeMessage.author' => ['sometimes', 'string', 'max:200'],
            'welcomeMessage.authorTitle' => ['sometimes', 'string', 'max:200'],
            'welcomeMessage.paragraphs' => ['sometimes', 'array'],
            'welcomeMessage.paragraphs.*' => ['string', 'max:2000'],
            'welcomeMessage.approvedAt' => ['sometimes', 'nullable', 'date'],
            'brochure' => ['sometimes', 'array'],
            'brochure.title' => ['sometimes', 'string', 'max:200'],
            'brochure.fileName' => ['sometimes', 'string', 'max:200'],
            'brochure.url' => ['sometimes', 'string', 'max:500'],
            'brochure.sizeBytes' => ['sometimes', 'integer', 'min:0'],
            'brochure.version' => ['sometimes', 'string', 'max:32'],
            'brochure.publishedAt' => ['sometimes', 'nullable', 'date'],
            'brochureFile' => ['sometimes', 'file', 'mimes:pdf', 'max:10240'],
            'deadlines' => ['sometimes', 'array'],
            'deadlines.*' => ['date'],
            'policies' => ['sometimes', 'array'],
            'policies.approvalRequired' => ['sometimes', 'boolean'],
            'policies.certificateMinDaysAttended' => ['sometimes', 'integer', 'min:1', 'max:3'],
            'policies.travelDatesFrom' => ['sometimes', 'date_format:Y-m-d'],
            'policies.accommodationCheckInFrom' => ['sometimes', 'date_format:Y-m-d'],
            'policies.accommodationCheckOutBy' => ['sometimes', 'date_format:Y-m-d'],
            'policies.verification' => ['sometimes', 'array'],
            'policies.verification.codeLength' => ['sometimes', 'integer', 'between:4,8'],
            'policies.verification.codeTtlSeconds' => ['sometimes', 'integer', 'between:300,900'],
            'policies.verification.resendCooldownSeconds' => ['sometimes', 'integer', 'between:30,300'],
            'policies.verification.maxResends' => ['sometimes', 'integer', 'between:0,10'],
            'policies.verification.maxAttempts' => ['sometimes', 'integer', 'between:3,10'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $event = app(EventService::class)->current();
                $policies = array_merge($event->policies ?? [], $this->input('policies', []));
                $start = $this->input('startDate', $event->start_date->format('Y-m-d'));
                $end = $this->input('endDate', $event->end_date->format('Y-m-d'));
                $travelFrom = $policies['travelDatesFrom'] ?? null;
                $checkIn = $policies['accommodationCheckInFrom'] ?? null;
                $checkOut = $policies['accommodationCheckOutBy'] ?? null;

                if (is_string($travelFrom) && $travelFrom > $end) {
                    $validator->errors()->add('policies.travelDatesFrom', 'Must be a date on or before the last summit day.');
                }

                if (is_string($checkIn) && is_string($checkOut) && strtotime($checkOut) <= strtotime($checkIn)) {
                    $validator->errors()->add('policies.accommodationCheckOutBy', 'Must be after the earliest check-in date.');
                }

                foreach (($this->input('deadlines', [])) as $key => $value) {
                    if (Carbon::parse($value) === null) {
                        $validator->errors()->add("deadlines.{$key}", 'Must be an ISO 8601 date-time.');
                    }
                }
            },
        ];
    }
}

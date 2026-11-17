<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NpisApiException;
use App\Http\Requests\Npis\ConfirmVerificationRequest;
use App\Http\Requests\Npis\StoreVerificationRequest;
use App\Services\Npis\VerificationService;
use App\Support\Npis\Destination;
use Illuminate\Http\JsonResponse;

class VerificationController extends NpisApiController
{
    public function __construct(private readonly VerificationService $verifications) {}

    public function store(StoreVerificationRequest $request): JsonResponse
    {
        $channel = (string) $request->validated('channel');
        $destination = Destination::normalize($channel, (string) $request->validated('destination'));

        if ($channel === 'sms' && ! preg_match('/^\+234[789][01]\d{8}$/', $destination)) {
            $message = 'Enter a valid Nigerian mobile number (070, 080, 081, 090 or 091 network).';
            throw NpisApiException::make(422, 'INVALID_DESTINATION', $message, ['fields' => ['destination' => $message]]);
        }

        if ($channel === 'email'
            && (! preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $destination) || mb_strlen($destination) > 254)) {
            $message = 'Enter a valid email address, e.g. name@example.com.';
            throw NpisApiException::make(422, 'INVALID_DESTINATION', $message, ['fields' => ['destination' => $message]]);
        }

        return $this->success(
            $this->verifications->initiate($channel, $destination, (string) $request->validated('purpose', 'registration'), $request->ip()),
            'Verification code sent successfully',
            201
        );
    }

    public function show(string $verificationId): JsonResponse
    {
        return $this->success($this->verifications->show($verificationId), 'Verification status retrieved successfully');
    }

    public function resend(string $verificationId): JsonResponse
    {
        return $this->success($this->verifications->resend($verificationId), 'Verification code resent successfully');
    }

    public function confirm(ConfirmVerificationRequest $request, string $verificationId): JsonResponse
    {
        return $this->success(
            $this->verifications->confirm($verificationId, (string) $request->validated('code'), $request->ip()),
            'Contact verified successfully'
        );
    }
}

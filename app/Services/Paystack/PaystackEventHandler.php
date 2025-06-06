<?php

namespace App\Services\Paystack;

use App\Enum\PaymentType;
use App\Enum\PaystackEvent;
use App\Trait\PaymentTrait;
use Illuminate\Support\Facades\Log;

class PaystackEventHandler
{
    use PaymentTrait;

    public static function handle(array $event): void
    {
        $eventType = $event['event'];
        $data = $event['data'];

        switch ($eventType) {
            case PaystackEvent::CHARGE_SUCCESS:
                self::handleChargeSuccess($event);
                break;

            case PaystackEvent::TRANSFER_SUCCESS:
                PaystackService::handleTransferSuccess($data);
                break;

            case PaystackEvent::TRANSFER_FAILED:
                PaystackService::handleTransferFailed($data);
                break;

            case PaystackEvent::TRANSFER_REVERSED:
                PaystackService::handleTransferReversed($data);
                break;

            default:
                Log::warning("Unhandled Paystack event: {$eventType}", $data);
                break;
        }
    }

    private function handleChargeSuccess(array $event)
    {
        $data = $event['data'];
        $paymentType = $data['metadata']['payment_type'];

        switch ($paymentType) {
            case PaymentType::FUND_WALLET:
                $this->handleFundWallet($event);
                break;

            case PaymentType::TRIP_BOOKING:
                $alreadyProcessed = $this->isAlreadyProcessed($event);
                if ($alreadyProcessed) {
                    return response()->json(['status' => true, 'message' => 'Already processed'], 200);
                }

                $this->handleTripBooking($event);
                break;

            case PaymentType::PREMIUM_HIRE:
                $this->handlePremiumHire($event);
                break;

            default:
                Log::warning('Unknown payment type', ['payment_type' => $paymentType]);
                break;
        }
    }
}


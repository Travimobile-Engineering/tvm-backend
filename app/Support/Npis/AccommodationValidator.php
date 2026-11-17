<?php

namespace App\Support\Npis;

use App\Models\Npis\Bank;
use App\Models\Npis\SummitEvent;

class AccommodationValidator
{
    /**
     * @param  array<string, mixed>  $accommodation
     * @return array<string, string>
     */
    public function validate(array $accommodation, SummitEvent $event): array
    {
        $errors = [];
        $preference = $accommodation['preference'] ?? null;
        $from = (string) $event->policy('accommodationCheckInFrom');
        $until = (string) $event->policy('accommodationCheckOutBy');

        if (! in_array($preference, ['organizer_arranged', 'self_arranged'], true)) {
            return ['accommodation.preference' => 'Choose organizer-arranged or self-arranged accommodation.'];
        }

        if ($preference === 'organizer_arranged') {
            $checkIn = $accommodation['checkInDate'] ?? null;
            $checkOut = $accommodation['checkOutDate'] ?? null;

            if (! $checkIn) {
                $errors['accommodation.checkInDate'] = 'Enter your check-in date.';
            } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkIn) || $checkIn < $from || $checkIn > $until) {
                $errors['accommodation.checkInDate'] = "Check-in must be between {$from} and {$until}.";
            }

            if (! $checkOut) {
                $errors['accommodation.checkOutDate'] = 'Enter your check-out date.';
            } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOut) || $checkOut < $from || $checkOut > $until) {
                $errors['accommodation.checkOutDate'] = "Check-out must be between {$from} and {$until}.";
            } elseif ($checkIn && $checkOut <= $checkIn) {
                $errors['accommodation.checkOutDate'] = 'Check-out must be at least one night after check-in.';
            }
        } elseif (! empty($accommodation['checkInDate']) || ! empty($accommodation['checkOutDate'])) {
            $errors['accommodation.checkInDate'] = 'Dates are only needed for organizer-arranged accommodation.';
        }

        if (! empty($accommodation['specialNeeds']) && mb_strlen((string) $accommodation['specialNeeds']) > 500) {
            $errors['accommodation.specialNeeds'] = 'Keep this under 500 characters.';
        }

        $allowanceRequested = (bool) ($accommodation['allowanceRequested'] ?? false);

        if ($allowanceRequested) {
            if ($preference !== 'self_arranged') {
                $errors['accommodation.allowanceRequested'] = 'The accommodation allowance is only paid when you arrange your own accommodation.';
            } else {
                $account = $accommodation['payoutAccount'] ?? [];
                $bankCode = $account['bankCode'] ?? null;
                $number = (string) ($account['accountNumber'] ?? '');
                $name = trim((string) ($account['accountName'] ?? ''));

                if (! $bankCode || ! Bank::query()->whereKey($bankCode)->exists()) {
                    $errors['accommodation.payoutAccount.bankCode'] = 'Choose your bank.';
                }

                if (! preg_match('/^\d{10}$/', $number)) {
                    $errors['accommodation.payoutAccount.accountNumber'] = 'Enter your 10-digit account number (NUBAN).';
                }

                if (mb_strlen($name) < 3 || mb_strlen($name) > 100 || ! preg_match('/^[\p{L}][\p{L}\' .-]+$/u', $name)) {
                    $errors['accommodation.payoutAccount.accountName'] = 'Enter the account name exactly as your bank shows it.';
                }
            }
        } elseif (! empty($accommodation['payoutAccount'])) {
            $errors['accommodation.payoutAccount.bankCode'] = 'Bank details are only needed when you request the accommodation allowance.';
        }

        return $errors;
    }
}

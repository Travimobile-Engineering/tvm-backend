<?php

namespace App\Trait;

use App\Enum\ChargeType;
use App\Enum\DocumentStatus;
use App\Enum\General;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Enum\TransitCompanyType;
use App\Enum\TransactionTitle;
use App\Enum\UserType;
use App\Models\TransitCompany;
use App\Models\Wallet;
use App\Services\Admin\AccountService;
use Illuminate\Support\Facades\DB;

trait DriverTrait
{
    use HttpResponse;

    protected function createTransitCompany($user, $request)
    {
        return TransitCompany::create([
            'user_id' => $user->id,
            'name' => $user->first_name,
            'email' => $user->email,
            'phone' => $user->phone_number ?? 0,
            'union_id' => $request->transit_company_union_id,
            'union_states_chapter' => $request->union_states_chapter,
            'park' => $request->park,
            'type' => TransitCompanyType::INDIVIDUAL,
            'ver_code' => 0,
        ]);
    }

    protected function handleDriverDocumentUploads($request, $user, array $fileUploads)
    {
        $documentTypes = [
            'license_photo' => [
                'type' => 'license',
                'extra_fields' => [
                    'number' => $request->license_number,
                    'expiration_date' => $request->license_expiration_date,
                ]
            ],
            'nin_photo' => [
                'type' => 'nin',
                'extra_fields' => [
                    'number' => $request->nin,
                ]
            ],
            'vehicle_insurance_photo' => [
                'type' => 'vehicle_insurance',
                'extra_fields' => [
                    'expiration_date' => $request->vehicle_insurance_expiration_date,
                ]
            ],
        ];

        foreach ($documentTypes as $key => $docDetails) {
            $hasFile = !empty($fileUploads[$key]['url']);
            $hasExtraField = false;

            foreach ($docDetails['extra_fields'] as $field => $value) {
                if ($request->filled($field)) {
                    $hasExtraField = true;
                    break;
                }
            }

            if (!$hasFile && !$hasExtraField) {
                continue;
            }

            $user->documents()->create([
                'type' => $docDetails['type'],
                'image_url' => $fileUploads[$key]['url'] ?? null,
                'public_id' => $fileUploads[$key]['public_id'] ?? null,
                'number' => $docDetails['extra_fields']['number'] ?? null,
                'expiration_date' => $docDetails['extra_fields']['expiration_date'] ?? null,
                'status' => DocumentStatus::PENDING,
            ]);
        }
    }

    protected function chargeWallet($user, $amount = null, $trip = null, $driver = null)
    {
        $recipient = $this->determineEarningRecipient($user, $driver);

        if (! $recipient) {
            return;
        }

        try {
            DB::transaction(function () use ($recipient, $amount, $trip, $user) {
                // First, top up any pending earnings
                $this->topUpEarning($recipient);

                // Then charge the wallet (manifest fee or specified amount)
                $chargeAmount = $amount ?: getFee('manifest');

                if ($chargeAmount > 0) {
                    $this->driverDecrementEarning($recipient, $chargeAmount);

                    $title = TransactionTitle::CHARGE_WALLET->value;
                    $type = PaymentType::DR;

                    $departure = "{$trip->departureRegion?->state?->name} > {$trip->departureRegion?->name}";
                    $destination = "{$trip->destinationRegion?->state?->name} > {$trip->destinationRegion?->name}";

                    $this->createTransaction(
                        $recipient,
                        $chargeAmount,
                        $title,
                        $type,
                        "Manifest fee for trip from {$departure} to {$destination}"
                    );
                }
            });

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to charge wallet: ' . $e->getMessage());
        }
    }

    protected function topUpEarning($user)
    {
        if (! $user || $user->pending_balance <= 0) {
            return;
        }

        $pendingAmount = (float) $user->pending_balance;

        DB::transaction(function () use ($user, $pendingAmount) {
            // Increment driver earnings
            $this->driverIncrementEarning($user, $pendingAmount);

            // Update pending payments status (using query builder for efficiency)
            $user->driverTripPayments()
                ->where('status', General::PENDING)
                ->update(['status' => General::PAID]);

            // Create earning record
            $user->createEarning(
                TransactionTitle::TRIP_BOOKING->value,
                $pendingAmount,
                'CR',
                PaymentStatus::PAID->value,
                "Earnings top-up from trip payments."
            );
        });
    }

    protected function createTransaction($user, $amount, $title, $type, $description = null)
    {
        $user->transactions()->create([
            'title' => $title,
            'amount' => $amount,
            'type' => $type,
            'txn_reference' => generateReference('TXN', 'transactions'),
            'description' => $description,
        ]);

        if (app()->environment('production')) {
            app(AccountService::class)->initiateTransfer(
                [ChargeType::MANIFEST->value => $amount]
            );
        }
    }

    protected function uploadInteriorImages($request, $user)
    {
        $interiorImages = uploadFilesBatch(
            $request->file('vehicle_interior_images'),
            'driver/vehicle/interior'
        );

        foreach ($interiorImages as $image) {
            if ($image['url'] !== null) {
                $user->vehicle->vehicleImages()->create([
                    'type' => 'interior',
                    'url' => $image['url'],
                    'public_id' => $image['public_id'],
                ]);
            }
        }
    }

    protected function uploadExteriorImages($request, $user)
    {
        $exteriorImages = uploadFilesBatch(
            $request->file('vehicle_exterior_images'),
            'driver/vehicle/exterior'
        );

        foreach ($exteriorImages as $image) {
            if ($image['url'] !== null) {
                $user->vehicle->vehicleImages()->create([
                    'type' => 'exterior',
                    'url' => $image['url'],
                    'public_id' => $image['public_id'],
                ]);
            }
        }
    }

    protected function driverIncrementEarning($user, $amount, ?Wallet $lockedWallet = null)
    {
        $wallet = $lockedWallet ?? $user->walletAccount()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'earnings' => 0.00],
        );

        $wallet->increment('earnings', $amount);
    }

    protected function userDecrementBalance($user, $amount, ?Wallet $lockedWallet = null)
    {
        $wallet = $lockedWallet ?? $user->walletAccount()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'earnings' => 0.00],
        );

        $wallet->decrement('balance', $amount);
    }

    protected function driverDecrementEarning($user, $amount, ?Wallet $lockedWallet = null): void
    {
        $wallet = $lockedWallet ?? $user->walletAccount()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'earnings' => 0.00],
        );

        $wallet->decrement('earnings', $amount);
    }

    protected function userIncrementBalance($user, $amount, ?Wallet $lockedWallet = null): void
    {
        $wallet = $lockedWallet ?? $user->walletAccount()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'earnings' => 0.00],
        );

        $wallet->increment('balance', $amount);
    }

    /**
     * Determine the appropriate recipient for earnings
     */
    private function determineEarningRecipient($user, $driver): ?object
    {
        if ($user->user_category !== UserType::DRIVER->value && $driver) {
            return $driver;
        }

        if ($user->user_category === UserType::DRIVER->value) {
            return $user;
        }

        return null;
    }
}




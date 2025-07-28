<?php

namespace App\Trait;

use App\Enum\DocumentStatus;
use App\Enum\PaymentType;
use App\Enum\TransitCompanyType;
use App\Models\TransitCompany;
use App\Services\Admin\AccountService;

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

    protected function chargeWallet($user, $amount = null)
    {
        $amount = $amount ?: getFee('manifest');

        $this->driverDecrementEarning($user, $amount);

        $user->save();

        $title = "Wallet charged for manifest";
        $type = PaymentType::DR;

        $this->createTransaction($user, $amount, $title, $type);
    }

    protected function topUpWallet($user)
    {
        $pendingAmount = $user->driverTripPayments->where('status', 'pending')->sum('amount');

        if ($pendingAmount > 0) {
            $this->driverIncrementEarning($user, $pendingAmount);

            $user->driverTripPayments->where('status', 'pending')->each(function ($payment) {
                $payment->update(['status' => 'paid']);
            });

            $title = "Credit wallet from pending balance";
            $type = PaymentType::CR;

            $this->createTransaction($user, $pendingAmount, $title, $type);
        }
    }

    protected function createTransaction($user, $amount, $title, $type)
    {
        $user->transactions()->create([
            'title' => $title,
            'amount' => $amount,
            'type' => $type,
            'txn_reference' => getRandomNumber()
        ]);

        if (app()->environment('production')) {
            app(AccountService::class)->initiateTransfer($amount);
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

    protected function driverIncrementEarning($user, $amount)
    {
        $wallet = $user->walletAccount()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0.00,
                'earnings' => 0.00,
            ]
        );

        $wallet->increment('earnings', $amount);
    }

    protected function driverDecrementEarning($user, $amount)
    {
        $wallet = $user->walletAccount()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0.00,
                'earnings' => 0.00,
            ]
        );

        $wallet->decrement('earnings', $amount);
    }

    protected function userIncrementBalance($user, $amount)
    {
        $wallet = $user->walletAccount()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0.00,
                'earnings' => 0.00,
            ]
        );

        $wallet->increment('balance', $amount);
    }

    protected function userDecrementBalance($user, $amount)
    {
        $wallet = $user->walletAccount()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0.00,
                'earnings' => 0.00,
            ]
        );

        $wallet->decrement('balance', $amount);
    }
}




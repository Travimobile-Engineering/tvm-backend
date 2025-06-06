<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\State;
use App\Services\Curl\GetCurlService;
use App\Trait\HttpResponse;
use Illuminate\Support\Facades\Cache;

class OtherService
{
    use HttpResponse;

    public function getStates()
    {
        $states = State::select('id', 'name')->get();

        return $this->success($states, 'States retrieved successfully');
    }

    public function getBank()
    {
        $banks = Cache::remember('banks_list', 43200, function () {
            $banks = Bank::select('id', 'name', 'slug', 'code')->get();

            if ($banks->isNotEmpty()) {
                return $banks;
            }

            return null;
        });

        if (empty($banks)) {
            return $this->error('No banks found', 404);
        }

        return $this->success($banks, 'Banks retrieved successfully');
    }

    public function accountLookUp($request)
    {
        $url = config('services.paystack_base_url') . "/resolve?account_number=". $request->account_number . "&bank_code=". $request->bank_code;
        $token = config('app.paystack_secret_key');

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ];

        return (new GetCurlService($url, $headers))->execute();
    }
}




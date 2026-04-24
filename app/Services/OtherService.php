<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\State;
use App\Services\Curl\GetCurlService;
use App\Trait\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
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
        $url = config('services.paystack_base_url').'/resolve?account_number='.$request->account_number.'&bank_code='.$request->bank_code;
        $token = config('app.paystack_secret_key');

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ];

        return (new GetCurlService($url, $headers))->execute();
    }

    public function clearRouteCache(): JsonResponse
    {
        try {
            Artisan::call('route:clear');
            Artisan::call('route:cache');

            return response()->json([
                'success' => true,
                'message' => 'Route cache cleared and rebuilt successfully.',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear route cache.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function clearAllCache(): JsonResponse
    {
        try {
            Artisan::call('optimize:clear');

            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully.',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Services;

use App\Models\IpWhitelist;
use App\Trait\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class IpWhitelistService
{
    use HttpResponse;

    public function index($request): JsonResponse
    {
        $airlineId = $request->query('airline_id');

        if (blank($airlineId)) {
            return $this->error(null, 'Airline Id required', 422);
        }

        $ips = IpWhitelist::where('airline_id', $airlineId)
            ->latest()
            ->paginate(20);

        return $this->withPagination($ips, 'Ip list');
    }

    public function store($request): JsonResponse
    {
        $ip = IpWhitelist::create([
            ...$request->validated(),
            'is_active' => true,
            'created_by' => $request->user()?->id,
        ]);

        Cache::forget("ip_whitelist:{$ip->ip_address}");

        return $this->success(null, 'Ip Address added successfully', 201);
    }

    public function show($request, int $id): JsonResponse
    {
        $airlineId = $request->query('airline_id');

        if (blank($airlineId)) {
            return $this->error(null, 'Airline Id required', 422);
        }

        $ip = IpWhitelist::findOrFail($id);

        return $this->success($ip, 'Ip details');
    }

    public function update($ip, $data): JsonResponse
    {
        $ip->update($data);
        Cache::forget("ip_whitelist:{$ip->ip_address}");

        return $this->success($ip->fresh(), 'Updated successfully');
    }

    public function destroy($request, int $id): JsonResponse
    {
        $airlineId = $request->query('airline_id');

        if (blank($airlineId)) {
            return $this->error(null, 'Airline Id required', 422);
        }

        $ip = IpWhitelist::where('airline_id', $request->airline_id)
            ->where('id', $id)
            ->first();

        if (! $ip) {
            return $this->error(null, 'Ip address not found!', 404);
        }

        Cache::forget("ip_whitelist:{$ip->ip_address}");
        $ip->delete();

        return $this->success(null, 'IP removed from whitelist.');
    }

    public function toggle(int $id): JsonResponse
    {
        $ip = IpWhitelist::findOrFail($id);

        $ip->update(['is_active' => ! $ip->is_active]);
        Cache::forget("ip_whitelist:{$ip->ip_address}");

        return $this->success(['is_active' => $ip->fresh()->is_active], 'Toggled');
    }

    public function check($request): JsonResponse
    {
        $ip = $request->input('ip_address');
        $record = IpWhitelist::where('ip_address', $ip)->first();
        $allowed = $record && $record->is_active && ! $record->isExpired();

        return $this->success([
            'ip' => $ip,
            'allowed' => $allowed,
            'record' => $record,
        ], 'Detail');
    }
}

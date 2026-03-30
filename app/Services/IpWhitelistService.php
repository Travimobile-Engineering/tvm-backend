<?php

namespace App\Services;

use App\Models\IpWhitelist;
use App\Trait\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class IpWhitelistService
{
    use HttpResponse;

    public function index(): JsonResponse
    {
        $ips = IpWhitelist::latest()->paginate(20);

        return $this->success($ips, 'Ip list');
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

    public function show(int $id): JsonResponse
    {
        $ip = IpWhitelist::findOrFail($id);

        return $this->success($ip, 'Ip details');
    }

    public function update($ip, $data): JsonResponse
    {
        $ip->update($data);
        Cache::forget("ip_whitelist:{$ip->ip_address}");

        return $this->success($ip->fresh(), 'Updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $ip = IpWhitelist::findOrFail($id);
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

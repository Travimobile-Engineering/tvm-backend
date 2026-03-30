<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIpWhiteListRequest;
use App\Models\IpWhitelist;
use App\Services\IpWhitelistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IpWhitelistController extends Controller
{
    public function __construct(
        private readonly IpWhitelistService $ipWhiteListService
    ) {}

    public function index(): JsonResponse
    {
        return $this->ipWhiteListService->index();
    }

    public function store(StoreIpWhiteListRequest $request): JsonResponse
    {
        return $this->ipWhiteListService->store($request);
    }

    public function show(int $id): JsonResponse
    {
        return $this->ipWhiteListService->show($id);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $ip = IpWhitelist::findOrFail($id);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'ip_address' => ['nullable', 'ip', Rule::unique('ip_whitelists')->ignore($ip->id)],
        ]);

        return $this->ipWhiteListService->update($ip, $data);
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->ipWhiteListService->destroy($id);
    }

    public function toggle(int $id): JsonResponse
    {
        return $this->ipWhiteListService->toggle($id);
    }

    public function check(Request $request): JsonResponse
    {
        $request->validate(['ip_address' => ['required', 'ip']]);

        return $this->ipWhiteListService->check($request);
    }
}

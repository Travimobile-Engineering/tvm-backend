<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIpWhiteListRequest;
use App\Models\IpWhitelist;
use App\Services\IpWhitelistService;
use App\Trait\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IpWhitelistController extends Controller
{
    use HttpResponse;

    public function __construct(
        private readonly IpWhitelistService $ipWhiteListService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->ipWhiteListService->index($request);
    }

    public function store(StoreIpWhiteListRequest $request): JsonResponse
    {
        return $this->ipWhiteListService->store($request);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->ipWhiteListService->show($request, $id);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate(['airline_id' => ['required', 'integer']]);

        $ip = IpWhitelist::where('airline_id', $request->airline_id)
            ->where('id', $id)
            ->first();

        if (! $ip) {
            return $this->error(null, 'Ip address not found!', 404);
        }

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'ip_address' => ['nullable', 'ip', Rule::unique('ip_whitelists')->ignore($ip->id)],
        ]);

        return $this->ipWhiteListService->update($ip, $data);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->ipWhiteListService->destroy($request, $id);
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

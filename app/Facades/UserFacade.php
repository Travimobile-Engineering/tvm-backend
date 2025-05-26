<?php

namespace App\Facades;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserFacade
{
    protected static int $ttl = 120;

    public static function find($userId): ?array
    {
        return Cache::remember("user:{$userId}", self::$ttl, function () use ($userId) {
            $response = Http::withHeaders([
                'X-App-Service' => config('services.auth_service.name'),
                config('security.auth_header_key') => config('security.auth_header_value'),
            ])->get(config('services.auth_service.url') . "/users/{$userId}");

            $data = $response->json();

            if ($data['status']) {
                return $data['data'];
            }

            Log::warning("Failed to fetch user {$userId} from Auth Service", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        });
    }
    public static function batchFind(array $userIds): array
    {
        $results = [];
        $uncachedIds = [];

        foreach ($userIds as $id) {
            $cached = Cache::get("user:{$id}");
            if ($cached) {
                $results[$id] = $cached;
            } else {
                $uncachedIds[] = $id;
            }
        }

        if (!empty($uncachedIds)) {
            $response = Http::withHeaders([
                'X-App-Service' => config('services.auth_service.name'),
                config('security.auth_header_key') => config('security.auth_header_value'),
            ])->post(config('services.auth_service.url') . "/users/batch", [
                'user_ids' => $uncachedIds,
            ]);

            $data = $response->json();

            if ($data['status']) {
                $fetched = collect($data['data'])->keyBy('id')->toArray();

                foreach ($fetched as $id => $user) {
                    Cache::put("user:{$id}", $user, self::$ttl);
                    $results[$id] = $user;
                }
            } else {
                Log::warning("Failed to batch fetch users", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        }

        return $results;
    }
}

<?php

namespace App\Services\Npis;

use App\Models\Npis\Airport;
use App\Models\Npis\Bank;
use App\Models\Npis\Command;
use App\Models\Npis\Rank;
use App\Models\Npis\State;
use App\Models\Npis\Unit;
use Illuminate\Support\Facades\Cache;

class ReferenceDataService
{
    public const CACHE_RANKS = 'npis:reference:ranks';

    public const CACHE_STATES = 'npis:reference:states';

    public const CACHE_AIRPORTS = 'npis:reference:airports';

    public const CACHE_BANKS = 'npis:reference:banks';

    public function __construct(private readonly EventService $events) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function ranks(): array
    {
        return Cache::remember(self::CACHE_RANKS, $this->ttl(), fn (): array => Rank::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Rank $rank): array => [
                'code' => $rank->code,
                'name' => $rank->name,
                'abbreviation' => $rank->abbreviation,
                'category' => $rank->category,
            ])->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function states(): array
    {
        return Cache::remember(self::CACHE_STATES, $this->ttl(), fn (): array => State::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (State $state): array => [
                'code' => $state->code,
                'name' => $state->name,
            ])->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function commands(string $stateCode): array
    {
        return Cache::remember('npis:reference:commands:'.$stateCode, $this->ttl(), fn (): array => Command::query()
            ->where('state_code', $stateCode)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Command $command): array => [
                'commandId' => $command->id,
                'stateCode' => $command->state_code,
                'name' => $command->name,
                'type' => $command->type,
            ])->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function units(string $commandId): array
    {
        return Cache::remember('npis:reference:units:'.$commandId, $this->ttl(), fn (): array => Unit::query()
            ->where('command_id', $commandId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Unit $unit): array => [
                'unitId' => $unit->id,
                'commandId' => $unit->command_id,
                'name' => $unit->name,
                'type' => $unit->type,
            ])->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function airports(): array
    {
        return Cache::remember(self::CACHE_AIRPORTS, $this->ttl(), fn (): array => Airport::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Airport $airport): array => [
                'code' => $airport->code,
                'name' => $airport->name,
                'city' => $airport->city,
                'stateCode' => $airport->state_code,
            ])->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function banks(): array
    {
        return Cache::remember(self::CACHE_BANKS, $this->ttl(), fn (): array => Bank::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Bank $bank): array => [
                'code' => $bank->code,
                'name' => $bank->name,
            ])->all());
    }

    public function forgot(string $key): void
    {
        Cache::forget($key);
    }

    private function ttl(): int
    {
        return min(300, max(1, (int) config('npis.cache_ttl', 300)));
    }
}

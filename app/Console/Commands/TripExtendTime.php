<?php

namespace App\Console\Commands;

use App\Enum\TripStatus;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TripExtendTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trip:extend-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extend the departure time for active trips based on the user\'s trip extend time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $trips = Trip::with('user')
            ->where('status', TripStatus::ACTIVE)
            ->whereTime('departure_time', '<', Carbon::now()->format('H:i'))
            ->get();

        foreach ($trips as $trip) {
            if (! $trip->user) {
                $this->info("Trip ID {$trip->id} has no associated user.");

                continue;
            }

            $tripExtendTime = $trip->user->trip_extended_time;
            if (! $tripExtendTime || ! preg_match('/^\d{1,2}:\d{2}$/', $tripExtendTime)) {
                $this->error("Skipping Trip ID {$trip->id}: Invalid trip_extended_time ({$tripExtendTime}) for User ID {$trip->user->id}.");

                continue;
            }

            $departureTime = Carbon::parse($trip->departure_time);
            [$hours, $minutes] = explode(':', $tripExtendTime);

            $totalMinutes = ((int) $hours * 60) + (int) $minutes;
            $newDepartureTime = $departureTime->addMinutes($totalMinutes);

            try {
                $trip->update(['departure_time' => $newDepartureTime->format('H:i')]);
                $this->info("Trip ID {$trip->id} departure time extended to {$newDepartureTime->format('H:i')}");
            } catch (\Exception $e) {
                $this->error("Failed to update Trip ID {$trip->id}: ".$e->getMessage());
            }
        }
    }
}

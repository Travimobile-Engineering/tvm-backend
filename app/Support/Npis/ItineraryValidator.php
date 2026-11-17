<?php

namespace App\Support\Npis;

use App\Models\Npis\Airport;
use App\Models\Npis\SummitEvent;
use Illuminate\Support\Carbon;

class ItineraryValidator
{
    /**
     * @param  array<string, mixed>  $itinerary
     * @return array<string, string>
     */
    public function validate(array $itinerary, SummitEvent $event): array
    {
        $errors = [];
        $travelModes = ['air', 'road', 'rail', 'sea', 'undecided'];
        $planStatuses = ['undecided', 'preferred', 'confirmed'];
        $windows = ['early_morning', 'morning', 'afternoon', 'evening', 'night'];

        $travelMode = $itinerary['travelMode'] ?? null;
        if (! in_array($travelMode, $travelModes, true)) {
            $errors['itinerary.travelMode'] = 'Choose how you will travel to Abuja.';
        }

        $travelFrom = $event->policy('travelDatesFrom');
        $arrival = $itinerary['arrival'] ?? [];
        $departure = $itinerary['departure'] ?? [];

        $errors += $this->leg(
            $arrival,
            'itinerary.arrival',
            'arrival',
            (string) $travelFrom,
            $event->end_date->format('Y-m-d'),
            $planStatuses,
            $windows,
        );
        $errors += $this->leg(
            $departure,
            'itinerary.departure',
            'departure',
            $event->start_date->format('Y-m-d'),
            Carbon::parse($event->end_date)->addDays(14)->format('Y-m-d'),
            $planStatuses,
            $windows,
        );

        if (! isset($errors['itinerary.arrival.date'], $errors['itinerary.departure.date'])
            && ! empty($arrival['date']) && ! empty($departure['date'])) {
            $arrivalAt = Carbon::parse($arrival['date'].'T'.($arrival['time'] ?? '00:00'));
            $departureAt = Carbon::parse($departure['date'].'T'.($departure['time'] ?? '23:59'));

            if ($departureAt->lessThanOrEqualTo($arrivalAt)) {
                $errors['itinerary.departure.date'] = 'Departure must be after your arrival in Abuja.';
            }
        }

        if ($travelMode === 'undecided'
            && (($arrival['planStatus'] ?? null) === 'confirmed' || ($departure['planStatus'] ?? null) === 'confirmed')) {
            $errors['itinerary.travelMode'] = 'Choose a travel mode before marking a booking as confirmed.';
        }

        $postSummitPlan = $itinerary['postSummitPlan'] ?? null;
        if (! in_array($postSummitPlan, ['leave_immediately', 'extend_stay', 'undecided'], true)) {
            $errors['itinerary.postSummitPlan'] = 'Tell us whether you will leave after the summit or extend your stay.';
        } elseif (! isset($errors['itinerary.departure.date']) && ! empty($departure['date'])) {
            if ($postSummitPlan === 'extend_stay'
                && $departure['date'] <= $event->end_date->format('Y-m-d')) {
                $errors['itinerary.departure.date'] = 'If you are extending your stay, departure should be after '.$event->end_date->format('Y-m-d').'.';
            }

            if ($postSummitPlan === 'leave_immediately'
                && $departure['date'] > Carbon::parse($event->end_date)->addDay()->format('Y-m-d')) {
                $errors['itinerary.departure.date'] = 'This date is more than a day after the summit — choose “Extend stay” instead.';
            }
        }

        $flight = $itinerary['flight'] ?? null;

        if ($travelMode === 'air') {
            if (! is_array($flight)) {
                $errors['itinerary.flight'] = 'Add your flight preferences, or mark them as undecided.';
            } else {
                if (! empty($flight['departureAirportCode'])
                    && ! Airport::query()->whereKey($flight['departureAirportCode'])->exists()) {
                    $errors['itinerary.flight.departureAirportCode'] = 'Choose an airport from the list.';
                }

                if (! empty($flight['flightTime']) && ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $flight['flightTime'])) {
                    $errors['itinerary.flight.flightTime'] = 'Use a 24-hour time, e.g. 07:45.';
                }

                if (! empty($flight['flightTime']) && ! empty($flight['flightTimeWindow'])) {
                    $errors['itinerary.flight.flightTimeWindow'] = 'Give an exact time or a window, not both.';
                }

                if (! empty($flight['flightNumber'])) {
                    if (($arrival['planStatus'] ?? null) !== 'confirmed') {
                        $errors['itinerary.flight.flightNumber'] = 'Only add a flight number once your booking is confirmed.';
                    } elseif (! preg_match('/^[A-Z0-9]{2,3}\s?\d{1,4}$/', strtoupper($flight['flightNumber']))) {
                        $errors['itinerary.flight.flightNumber'] = 'Use the format on your ticket, e.g. P4 7121.';
                    }
                }
            }
        } elseif ($flight !== null) {
            $errors['itinerary.flight'] = 'Flight details only apply to air travel.';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $leg
     * @param  list<string>  $planStatuses
     * @param  list<string>  $windows
     * @return array<string, string>
     */
    private function leg(array $leg, string $prefix, string $label, string $min, string $max, array $planStatuses, array $windows): array
    {
        $errors = [];

        if (! in_array($leg['planStatus'] ?? null, $planStatuses, true)) {
            $errors[$prefix.'.planStatus'] = "Choose whether your {$label} is undecided, a preference or confirmed.";
        }

        if (($leg['planStatus'] ?? null) !== 'undecided' && empty($leg['date'])) {
            $errors[$prefix.'.date'] = "Enter your expected {$label} date, or mark it as undecided.";
        }

        if (! empty($leg['date'])) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $leg['date'])) {
                $errors[$prefix.'.date'] = 'Use a valid date.';
            } elseif ($leg['date'] < $min || $leg['date'] > $max) {
                $errors[$prefix.'.date'] = "Choose a {$label} date between {$min} and {$max}.";
            }
        }

        if (! empty($leg['time']) && ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $leg['time'])) {
            $errors[$prefix.'.time'] = 'Use a 24-hour time, e.g. 14:30.';
        }

        if (! empty($leg['time']) && ! empty($leg['timeWindow'])) {
            $errors[$prefix.'.timeWindow'] = 'Give an exact time or a time window, not both.';
        }

        if (! empty($leg['timeWindow']) && ! in_array($leg['timeWindow'], $windows, true)) {
            $errors[$prefix.'.timeWindow'] = 'Choose a time window.';
        }

        if (($leg['planStatus'] ?? null) === 'confirmed' && empty($leg['time']) && empty($leg['timeWindow'])) {
            $errors[$prefix.'.time'] = "A confirmed {$label} needs a time or time window.";
        }

        return $errors;
    }
}

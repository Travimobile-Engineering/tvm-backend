<?php

namespace App\Support\Npis;

use App\Models\Npis\Command;
use App\Models\Npis\Rank;
use App\Models\Npis\State;
use App\Models\Npis\SummitEvent;
use App\Models\Npis\Unit;

class RegistrationValidator
{
    public function __construct(
        private readonly ItineraryValidator $itineraryValidator,
        private readonly AccommodationValidator $accommodationValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    public function validate(array $payload, SummitEvent $event): array
    {
        $participantType = $payload['participantType'] ?? null;

        if (! in_array($participantType, ['delegate', 'vendor'], true)) {
            return ['participantType' => 'Choose whether you are registering as a delegate or a vendor.'];
        }

        $profile = $payload['profile'] ?? [];
        $errors = $this->profileErrors($profile, $participantType);
        $delegate = $participantType === 'delegate';
        $police = $delegate && ($profile['sector'] ?? null) === 'police';

        if ($delegate) {
            $errors += isset($payload['itinerary']) && is_array($payload['itinerary'])
                ? $this->itineraryValidator->validate($payload['itinerary'], $event)
                : ['itinerary.travelMode' => 'Add your travel plans, or mark them as undecided.'];
        } elseif (isset($payload['itinerary'])) {
            $errors['itinerary'] = "Vendors don't provide travel plans.";
        }

        $accommodationEligible = $delegate && ($profile['sector'] ?? null) !== null && ($profile['sector'] ?? null) !== 'police';

        if ($accommodationEligible) {
            $errors += isset($payload['accommodation']) && is_array($payload['accommodation'])
                ? $this->accommodationValidator->validate($payload['accommodation'], $event)
                : ['accommodation.preference' => "Tell us whether you'd like the organizers to arrange your accommodation."];
        } elseif (isset($payload['accommodation'])) {
            $errors['accommodation'] = $participantType === 'vendor'
                ? 'Vendors are not provided accommodation.'
                : 'Accommodation options are not offered to police officers.';
        }

        if ($delegate) {
            $errors += isset($payload['interests']) && is_array($payload['interests'])
                ? $this->interestErrors($payload['interests'])
                : ['interests.track' => 'Choose Transportation, Hospitality or Both.'];
        } elseif (isset($payload['interests'])) {
            $errors['interests'] = 'Programme interests only apply to delegates.';
        }

        if (isset($payload['photo']) && $payload['photo'] !== null) {
            if (! is_string($payload['photo'])
                || ! preg_match('/^data:image\/(jpeg|png|webp);base64,[A-Za-z0-9+\/]+=*$/', $payload['photo'])) {
                $errors['photo'] = 'Upload a JPEG, PNG or WebP photo.';
            } elseif (strlen($payload['photo']) > (int) config('npis.media.photo_max_bytes')) {
                $errors['photo'] = 'This photo is too large. Use one under 300 KB.';
            }
        }

        if (! (bool) ($payload['consent']['dataProcessing'] ?? false)) {
            $errors['consent.dataProcessing'] = 'Confirm that your details may be used to plan the summit.';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, string>
     */
    private function profileErrors(array $profile, string $participantType): array
    {
        $errors = [];
        $police = $participantType === 'delegate' && ($profile['sector'] ?? null) === 'police';
        $fullName = trim((string) ($profile['fullName'] ?? ''));

        if (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 100
            || ! preg_match('/^[\p{L}][\p{L}\' .-]+$/u', $fullName)) {
            $errors['profile.fullName'] = 'Enter your full name as it appears on your '.($police ? 'police ID' : 'ID card').' (letters only).';
        } elseif (count(preg_split('/\s+/', $fullName) ?: []) < 2) {
            $errors['profile.fullName'] = 'Enter at least your first name and surname.';
        }

        if ($participantType === 'vendor') {
            $services = ['usher', 'caterer', 'cleaner', 'security', 'photographer', 'videographer', 'journalist', 'driver', 'decorator', 'other'];
            $service = $profile['vendorService'] ?? null;

            if (! in_array($service, $services, true)) {
                $errors['profile.vendorService'] = 'Choose the service you will provide.';
            } elseif ($service === 'other') {
                $other = trim((string) ($profile['vendorServiceOther'] ?? ''));
                if (mb_strlen($other) < 2 || mb_strlen($other) > 80) {
                    $errors['profile.vendorServiceOther'] = 'Describe the service you will provide (2–80 characters).';
                }
            }

            $organization = trim((string) ($profile['organization'] ?? ''));
            if (mb_strlen($organization) < 2 || mb_strlen($organization) > 120) {
                $errors['profile.organization'] = 'Enter your company or organization (2–120 characters).';
            }

            $this->maxLength($errors, 'profile.designation', $profile['designation'] ?? null, 120, 'designation');

            return $errors;
        }

        $sectors = ['police', 'security_agency', 'government', 'transport', 'hospitality', 'private_sector', 'media', 'other'];
        $sector = $profile['sector'] ?? null;

        if (! in_array($sector, $sectors, true)) {
            return $errors + ['profile.sector' => 'Choose your sector.'];
        }

        if (! $police) {
            $organization = trim((string) ($profile['organization'] ?? ''));
            if (mb_strlen($organization) < 2 || mb_strlen($organization) > 120) {
                $errors['profile.organization'] = 'Enter the organization you represent (2–120 characters).';
            }

            $this->maxLength($errors, 'profile.designation', $profile['designation'] ?? null, 120, 'designation');

            return $errors;
        }

        if (! Rank::query()->whereKey($profile['rankCode'] ?? '')->exists()) {
            $errors['profile.rankCode'] = 'Select your rank.';
        }

        $this->maxLength($errors, 'profile.designation', $profile['designation'] ?? null, 120, 'designation');

        if (! State::query()->whereKey($profile['stateCode'] ?? '')->exists()) {
            $errors['profile.stateCode'] = 'Select your state or FCT.';
        }

        $command = Command::query()->find($profile['commandId'] ?? '');

        if (! $command || $command->state_code !== ($profile['stateCode'] ?? null)) {
            $errors['profile.commandId'] = 'Select your command.';
        }

        if (! empty($profile['unitId'])) {
            $unit = Unit::query()->find($profile['unitId']);

            if (! $unit || $unit->command_id !== ($profile['commandId'] ?? null)) {
                $errors['profile.unitId'] = 'Select your division or unit.';
            }
        } elseif (! array_key_exists('unitOtherName', $profile) || $profile['unitOtherName'] === null) {
            $errors['profile.unitId'] = "Select your division or unit, or choose that it isn't listed.";
        } else {
            $other = trim((string) $profile['unitOtherName']);

            if (mb_strlen($other) < 2 || mb_strlen($other) > 120) {
                $errors['profile.unitOtherName'] = 'Type the name of your division or unit (2–120 characters).';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $interests
     * @return array<string, string>
     */
    private function interestErrors(array $interests): array
    {
        $errors = [];
        $track = $interests['track'] ?? null;

        if (! in_array($track, ['transportation', 'hospitality', 'both'], true)) {
            return ['interests.track' => 'Choose Transportation, Hospitality or Both.'];
        }

        $subsectors = $interests['transportSubsectors'] ?? [];

        if ($track === 'hospitality' && count($subsectors) > 0) {
            $errors['interests.transportSubsectors'] = 'Transport subsectors only apply to the Transportation track.';
        } elseif ($track !== 'hospitality') {
            if (count($subsectors) === 0) {
                $errors['interests.transportSubsectors'] = 'Select at least one transport subsector.';
            } elseif (count(array_unique($subsectors)) !== count($subsectors)
                || array_diff($subsectors, ['road', 'air', 'sea', 'rail']) !== []) {
                $errors['interests.transportSubsectors'] = 'Select from Road, Air, Sea and Rail.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, string>  $errors
     */
    private function maxLength(array &$errors, string $key, mixed $value, int $max, string $label): void
    {
        if ($value !== null && mb_strlen(trim((string) $value)) > $max) {
            $errors[$key] = "Keep the {$label} under {$max} characters.";
        }
    }
}

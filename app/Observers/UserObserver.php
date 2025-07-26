<?php

namespace App\Observers;

use App\Models\AgentClassification;
use App\Models\User;
use App\Enum\UserType;
use App\Models\Wallet;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class UserObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        Wallet::updateOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'earnings' => 0]
        );

        if ($user->user_category === UserType::AGENT->value) {
            $levelA = AgentClassification::where('level', 'A')->first();

            $user->updateQuietly([
                'classification_id' => $levelA?->id
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if (
            $user->user_category === UserType::PASSENGER->value &&
            $user->hasCompletedOnboarding() &&
            empty($user->referral_code)
        ) {
            $user->updateQuietly([
                'referral_code' => generateUniqueString('users', 'referral_code', 8),
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}

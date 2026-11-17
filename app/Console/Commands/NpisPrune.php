<?php

namespace App\Console\Commands;

use App\Models\Npis\AdminSession;
use App\Models\Npis\ContactVerification;
use App\Models\Npis\IdempotencyRecord;
use App\Models\Npis\OrganizerAccessGrant;
use App\Models\Npis\ParticipantSession;
use Illuminate\Console\Command;

class NpisPrune extends Command
{
    protected $signature = 'npis:prune';

    protected $description = 'Prune expired NPIS idempotency records, sessions, grants and verification challenges';

    public function handle(): int
    {
        $idempotency = IdempotencyRecord::query()->where('expires_at', '<', now())->delete();
        $participantSessions = ParticipantSession::query()
            ->where('expires_at', '<', now()->subDay())
            ->orWhere('revoked_at', '<', now()->subDay())
            ->delete();
        $adminSessions = AdminSession::query()
            ->where('expires_at', '<', now()->subDay())
            ->orWhere('revoked_at', '<', now()->subDay())
            ->delete();
        $grants = OrganizerAccessGrant::query()
            ->where('expires_at', '<', now()->subDay())
            ->orWhere('revoked_at', '<', now()->subDay())
            ->delete();
        $verifications = ContactVerification::query()
            ->where('created_at', '<', now()->subDays(2))
            ->delete();

        $this->info(sprintf(
            'Pruned %d idempotency records, %d participant sessions, %d admin sessions, %d access grants and %d verification challenges.',
            $idempotency,
            $participantSessions,
            $adminSessions,
            $grants,
            $verifications
        ));

        return self::SUCCESS;
    }
}

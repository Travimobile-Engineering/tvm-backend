<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DataProtection\DataProtector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypts sensitive values that were stored as plaintext before the data
 * protection layer was introduced. The command is idempotent: values that are
 * already encrypted are skipped, so it is safe to run more than once.
 *
 * Note: columns already encrypted with the legacy encryptData() helper (users.nin)
 * are intentionally excluded and handled separately.
 */
class EncryptSensitiveData extends Command
{
    protected $signature = 'data-protection:encrypt
        {--chunk=500 : Number of rows processed per batch}
        {--dry-run : Report the values that would be encrypted without writing}';

    protected $description = 'Encrypt existing plaintext values in sensitive database columns';

    /**
     * @var array<string, list<string>>
     */
    private array $targets = [
        'users' => [
            'address',
            'next_of_kin_full_name',
            'next_of_kin_phone_number',
            'lng',
            'lat',
        ],
        'user_banks' => [
            'account_name',
        ],
        'trip_booking_passengers' => [
            'email',
            'phone_number',
            'next_of_kin',
            'next_of_kin_phone_number',
        ],
        'premium_hire_booking_passengers' => [
            'email',
            'phone_number',
            'next_of_kin',
            'next_of_kin_phone_number',
        ],
        'agents' => [
            'residential_address',
        ],
    ];

    /**
     * Searchable columns that also need a deterministic blind index kept in
     * sync. Map of table => [column => {hash column, normalize}].
     *
     * @var array<string, array<string, array{column: string, normalize: bool}>>
     */
    private array $hashedTargets = [
        'users' => [
            'email' => ['column' => 'email_hash', 'normalize' => true],
            'phone_number' => ['column' => 'phone_number_hash', 'normalize' => true],
        ],
        'user_banks' => [
            'account_number' => ['column' => 'account_number_hash', 'normalize' => true],
        ],
        'documents' => [
            'number' => ['column' => 'number_hash', 'normalize' => true],
        ],
        'agents' => [
            'email' => ['column' => 'email_hash', 'normalize' => true],
            'phone' => ['column' => 'phone_hash', 'normalize' => true],
        ],
    ];

    public function handle(DataProtector $protector): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach ($this->targets as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $this->warn("Skipping {$table}: table does not exist.");

                continue;
            }

            $encrypted = 0;

            $query = DB::table($table)->select(array_merge(['id'], $columns));

            // Only fetch rows that still hold at least one plaintext value so
            // repeated runs are cheap.
            $query->where(function ($query) use ($columns): void {
                foreach ($columns as $column) {
                    $query->orWhere(function ($query) use ($column): void {
                        $query->whereNotNull($column)
                            ->where($column, '!=', '')
                            ->where($column, 'not like', DataProtector::PREFIX.'%');
                    });
                }
            });

            $query
                ->orderBy('id')
                ->chunkById($chunk, function ($rows) use ($columns, $table, $protector, $dryRun, &$encrypted): void {
                    foreach ($rows as $row) {
                        $updates = [];

                        foreach ($columns as $column) {
                            $value = $row->{$column} ?? null;

                            if (is_string($value) && $value !== '' && ! $protector->isEncrypted($value)) {
                                $updates[$column] = $protector->encrypt($value);
                            }
                        }

                        if ($updates === []) {
                            continue;
                        }

                        $encrypted += count($updates);

                        if (! $dryRun) {
                            DB::table($table)->where('id', $row->id)->update($updates);
                        }
                    }
                });

            $total += $encrypted;
            $this->info(sprintf('%s: %d value(s) %s.', $table, $encrypted, $dryRun ? 'to encrypt' : 'encrypted'));
        }

        foreach ($this->hashedTargets as $table => $columns) {
            $total += $this->encryptHashedColumns($protector, $table, $columns, $chunk, $dryRun);
        }

        $this->info(sprintf('Done. %d value(s) %s.', $total, $dryRun ? 'pending (dry run)' : 'encrypted'));

        return self::SUCCESS;
    }

    /**
     * Encrypt a searchable column (if still plaintext) and populate its blind
     * index for every row where either is missing.
     *
     * @param  array<string, array{column: string, normalize: bool}>  $columns
     */
    private function encryptHashedColumns(DataProtector $protector, string $table, array $columns, int $chunk, bool $dryRun): int
    {
        if (! Schema::hasTable($table)) {
            $this->warn("Skipping {$table}: table does not exist.");

            return 0;
        }

        $processed = 0;

        foreach ($columns as $column => $config) {
            $hashColumn = $config['column'];
            $normalize = $config['normalize'];

            if (! Schema::hasColumn($table, $column) || ! Schema::hasColumn($table, $hashColumn)) {
                continue;
            }

            $count = 0;

            DB::table($table)
                ->select(['id', $column, $hashColumn])
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->where(function ($query) use ($column, $hashColumn): void {
                    $query->whereNull($hashColumn)
                        ->orWhere($column, 'not like', DataProtector::PREFIX.'%');
                })
                ->orderBy('id')
                ->chunkById($chunk, function ($rows) use ($protector, $table, $column, $hashColumn, $normalize, $dryRun, &$count): void {
                    foreach ($rows as $row) {
                        $raw = $row->{$column};

                        if (! is_string($raw) || $raw === '') {
                            continue;
                        }

                        $plaintext = $protector->isEncrypted($raw) ? $protector->decrypt($raw) : $raw;

                        if ($plaintext === null) {
                            continue;
                        }

                        $updates = [];

                        if (! $protector->isEncrypted($raw)) {
                            $updates[$column] = $protector->encrypt($plaintext);
                        }

                        $hash = $protector->blindIndex($plaintext, $normalize);

                        if ($row->{$hashColumn} !== $hash) {
                            $updates[$hashColumn] = $hash;
                        }

                        if ($updates === []) {
                            continue;
                        }

                        $count += count($updates);

                        if (! $dryRun) {
                            DB::table($table)->where('id', $row->id)->update($updates);
                        }
                    }
                });

            $processed += $count;
            $this->info(sprintf('%s.%s: %d value(s) %s.', $table, $column, $count, $dryRun ? 'to process' : 'encrypted'));
        }

        return $processed;
    }
}

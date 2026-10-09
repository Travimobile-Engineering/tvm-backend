<?php

use App\Services\DataProtection\DataProtector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PII/bank details on the admin payment accounts and the Paystack payloads
 * stored on transfer records are encrypted at rest. The affected columns are
 * widened so encrypted payloads fit. The two "response" columns were MySQL JSON
 * columns, but an encrypted payload is a plain string and not valid JSON, so
 * they are converted to TEXT (the change is reversible in down()).
 */
return new class extends Migration
{
    /**
     * Plaintext columns widened to TEXT so encrypted payloads fit.
     *
     * @var array<string, list<string>>
     */
    private array $widen = [
        'accounts' => [
            'account_name',
            'account_number',
        ],
    ];

    /**
     * JSON columns converted to TEXT (encrypted payloads are not valid JSON).
     *
     * @var array<string, list<string>>
     */
    private array $widenJson = [
        'account_transfers' => [
            'response',
        ],
        'admin_bulk_transfers' => [
            'response',
        ],
    ];

    public function up(): void
    {
        foreach (array_merge($this->widen, $this->widenJson) as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->text($column)->nullable()->change();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Decrypt first so reverting the JSON columns cannot fail.
        $this->decryptAll();

        foreach ($this->widen as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->string($column)->nullable()->change();
                    }
                }
            });
        }

        foreach ($this->widenJson as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->json($column)->nullable()->change();
                    }
                }
            });
        }
    }

    private function decryptAll(): void
    {
        $protector = app(DataProtector::class);

        foreach (array_merge($this->widen, $this->widenJson) as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)
                ->select(array_merge(['id'], $columns))
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($protector, $table, $columns): void {
                    foreach ($rows as $row) {
                        $updates = [];

                        foreach ($columns as $column) {
                            $value = $row->{$column} ?? null;

                            if ($protector->isEncrypted($value)) {
                                $updates[$column] = $protector->decrypt($value);
                            }
                        }

                        if ($updates !== []) {
                            DB::table($table)->where('id', $row->id)->update($updates);
                        }
                    }
                });
        }
    }
};

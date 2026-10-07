<?php

use App\Services\DataProtection\DataProtector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sensitive columns that must be widened so encrypted payloads fit. Map of
     * table => list of columns.
     *
     * @var array<string, list<string>>
     */
    private array $widen = [
        'user_banks' => [
            'account_number',
            'account_name',
        ],
        'documents' => [
            'number',
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
    ];

    /**
     * Deterministic blind index columns. Map of table => [column => hash column].
     *
     * @var array<string, array<string, string>>
     */
    private array $hashes = [
        'user_banks' => ['account_number' => 'account_number_hash'],
        'documents' => ['number' => 'number_hash'],
    ];

    public function up(): void
    {
        foreach ($this->widen as $table => $columns) {
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

        $this->addHashColumns();
    }

    public function down(): void
    {
        $this->decryptAll();
        $this->dropHashColumns();

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
    }

    private function addHashColumns(): void
    {
        foreach ($this->hashes as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = array_filter(
                $columns,
                fn (string $hashColumn, string $column): bool => Schema::hasColumn($table, $column)
                    && ! Schema::hasColumn($table, $hashColumn),
                ARRAY_FILTER_USE_BOTH
            );

            if ($existing === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($existing): void {
                foreach ($existing as $hashColumn) {
                    $blueprint->char($hashColumn, 64)->nullable();
                }
            });

            Schema::table($table, function (Blueprint $blueprint) use ($existing): void {
                foreach ($existing as $hashColumn) {
                    if (Schema::hasColumn($blueprint->getTable(), $hashColumn)) {
                        $blueprint->index($hashColumn);
                    }
                }
            });
        }
    }

    private function dropHashColumns(): void
    {
        foreach ($this->hashes as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $hashColumn) {
                if (! Schema::hasColumn($table, $hashColumn)) {
                    continue;
                }

                try {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex([$hashColumn]));
                } catch (Throwable) {
                    // Index already absent.
                }

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($hashColumn));
            }
        }
    }

    private function decryptAll(): void
    {
        $protector = app(DataProtector::class);

        foreach ($this->widen as $table => $columns) {
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

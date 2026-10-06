<?php

use App\Services\DataProtection\DataProtector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns that must be widened so encrypted payloads (longer than the
     * original plaintext) fit without truncation.
     *
     * @var array<string, list<string>>
     */
    private array $widen = [
        'users' => [
            'email',
            'phone_number',
            'address',
            'next_of_kin_full_name',
            'next_of_kin_phone_number',
        ],
    ];

    /**
     * Columns encrypted with the new data protection layer. Used to decrypt
     * data before the columns are narrowed again on rollback. Note: users.nin
     * is intentionally excluded because it uses the legacy encryptData() scheme.
     *
     * @var array<string, list<string>>
     */
    private array $encrypted = [
        'users' => [
            'email',
            'phone_number',
            'address',
            'next_of_kin_full_name',
            'next_of_kin_phone_number',
        ],
    ];

    public function up(): void
    {
        foreach ($this->widen as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table->getTable(), $column)) {
                        $table->text($column)->nullable()->change();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Decrypt first so narrowing the columns back cannot truncate payloads.
        $this->decryptAll();

        foreach ($this->widen as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table->getTable(), $column)) {
                        $table->string($column)->nullable()->change();
                    }
                }
            });
        }
    }

    private function decryptAll(): void
    {
        $protector = app(DataProtector::class);

        foreach ($this->encrypted as $table => $columns) {
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

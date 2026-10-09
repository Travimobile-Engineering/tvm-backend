<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deterministic blind index columns used to look up agents by their
     * encrypted email/phone, with a plaintext fallback for legacy rows. Map of
     * column => hash column.
     *
     * @var array<string, string>
     */
    private array $hashes = [
        'email' => 'email_hash',
        'phone' => 'phone_hash',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('agents')) {
            return;
        }

        Schema::table('agents', function (Blueprint $table): void {
            foreach ($this->hashes as $column => $hashColumn) {
                if (! Schema::hasColumn($table->getTable(), $hashColumn)) {
                    $table->char($hashColumn, 64)->nullable()->after($column);
                    $table->index($hashColumn);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agents')) {
            return;
        }

        Schema::table('agents', function (Blueprint $table): void {
            foreach ($this->hashes as $hashColumn) {
                if (Schema::hasColumn($table->getTable(), $hashColumn)) {
                    $table->dropIndex([$hashColumn]);
                    $table->dropColumn($hashColumn);
                }
            }
        });
    }
};

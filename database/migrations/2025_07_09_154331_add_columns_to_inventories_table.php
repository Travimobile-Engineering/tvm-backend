<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('inventories', 'code')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->string('code')->nullable()->after('name');
            });
        }

        // issued_item table
        if (Schema::hasColumn('issued_item', 'issued_to')) {
            Schema::table('issued_item', function (Blueprint $table) {
                $table->renameColumn('issued_to', 'user_id');
            });
        }

        if (Schema::hasColumn('issued_item', 'user_id')) {
            Schema::table('issued_item', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->change();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (Schema::hasColumn('issued_item', 'issues_date')) {
            Schema::table('issued_item', function (Blueprint $table) {
                $table->renameColumn('issues_date', 'issued_date');
            });
        }

        if (Schema::hasColumn('issued_item', 'item_id')) {
            Schema::table('issued_item', function (Blueprint $table) {
                $table->dropColumn('item_id');
            });
        }

        // Rename item_count table to stocks
        if (Schema::hasTable('item_count')) {
            Schema::rename('item_count', 'stocks');
        }

        // stocks table
        if (Schema::hasTable('stocks')) {
            if (Schema::hasColumn('stocks', 'item_id')) {
                Schema::table('stocks', function (Blueprint $table) {
                    $table->dropColumn('item_id');
                });
            }

            Schema::table('stocks', function (Blueprint $table) {
                if (!Schema::hasColumn('stocks', 'inventory_id')) {
                    $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('stocks', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventories', 'code')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }

        // issued_item table
        if (Schema::hasColumn('issued_item', 'user_id')) {
            Schema::table('issued_item', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->renameColumn('user_id', 'issued_to');
            });
        }

        if (Schema::hasColumn('issued_item', 'issued_date')) {
            Schema::table('issued_item', function (Blueprint $table) {
                $table->renameColumn('issued_date', 'issues_date');
            });
        }

        if (!Schema::hasColumn('issued_item', 'item_id')) {
            Schema::table('issued_item', function (Blueprint $table) {
                $table->foreignId('item_id')->constrained()->onDelete('cascade');
            });
        }

        // Rename stocks back to item_count
        if (Schema::hasTable('stocks')) {
            Schema::rename('stocks', 'item_count');
        }

        if (Schema::hasTable('item_count')) {
            if (Schema::hasColumn('item_count', 'inventory_id')) {
                Schema::table('item_count', function (Blueprint $table) {
                    $table->dropColumn(['inventory_id', 'created_at', 'updated_at']);
                });
            }

            if (!Schema::hasColumn('item_count', 'item_id')) {
                Schema::table('item_count', function (Blueprint $table) {
                    $table->foreignId('item_id')->constrained()->onDelete('cascade');
                });
            }
        }
    }
};

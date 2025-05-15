<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn('passenger');
            $table->dropColumn('driver');
            $table->dropColumn('premium_hire');
            $table->dropColumn('agent');
            $table->dropColumn('manifest');

            $table->string('name')->unique()->after('id');
            $table->double('amount')->default(0.00)->after('name');
            
            if (DB::getDriverName() !== 'sqlite') {
                DB::unprepared('DROP TRIGGER IF EXISTS limit_records');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {

            $table->dropColumn('name');
            $table->dropColumn('amount');

            $table->double('passenger')->default(0.00);
            $table->double('driver')->default(0.00);
            $table->double('premium_hire')->default(0.00);
            $table->double('agent')->default(0.00);
            $table->double('manifest')->default(0.00);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::unprepared('
                CREATE TRIGGER limit_records BEFORE INSERT ON fees
                FOR EACH ROW
                    BEGIN
                        IF (SELECT COUNT(*) FROM fees) > 0 THEN
                            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Maximum record limit reached";
                        END IF;
                    END
            ');
        }
    }
};

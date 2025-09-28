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
        $columns = Schema::getColumnListing('watch_lists');
        Schema::table('watch_lists', function (Blueprint $table) use ($columns) {
            $table->foreignId('state_id')->after('alert_location');
            $table->string('city');

            if (in_array('alert_location', $columns)) {
                $table->dropColumn('alert_location');
            }

            if (in_array('observation', $columns)) {
                $table->dropColumn('observation');
            }

            if (in_array('recent_location', $columns)) {
                $table->dropColumn('recent_location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watch_lists', function (Blueprint $table) {
            $table->dropColumn('state_id');
            $table->dropColumn('city');
            $table->string('alert_location');
            $table->string('recent_location')->nullable()->after('category');
            $table->text('observation')->nullable()->after('recent_location');
        });
    }
};

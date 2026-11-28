<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Not every programme numbers its events.
 *
 * The Junior Nationals programme in the 2026/2027 handbook is a plain list
 * of races with an empty Event column, and the federation has asked for it
 * to be published as printed rather than numbered by us. So the number
 * becomes optional, and a position carries the running order instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gala_events', function (Blueprint $table): void {
            $table->unsignedSmallInteger('event_number')->nullable()->change();
            $table->unsignedSmallInteger('position')->default(0)->after('gala_session_id');
        });

        // Existing programmes are already in event-number order.
        DB::statement('UPDATE gala_events SET position = event_number');
    }

    public function down(): void
    {
        DB::table('gala_events')->whereNull('event_number')->delete();

        Schema::table('gala_events', function (Blueprint $table): void {
            $table->dropColumn('position');
            $table->unsignedSmallInteger('event_number')->nullable(false)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An event number is unique within its round, not within the gala.
 *
 * Entering the national programmes from the handbook showed the original
 * constraint to be wrong: a championship numbers the heat and its final
 * alike, so event 201 is swum twice — once in the morning session and again
 * in the afternoon — and the pair could not both be recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gala_events', function (Blueprint $table): void {
            $table->dropUnique(['gala_id', 'event_number']);
            $table->unique(['gala_id', 'event_number', 'round']);
        });
    }

    public function down(): void
    {
        Schema::table('gala_events', function (Blueprint $table): void {
            $table->dropUnique(['gala_id', 'event_number', 'round']);
            $table->unique(['gala_id', 'event_number']);
        });
    }
};

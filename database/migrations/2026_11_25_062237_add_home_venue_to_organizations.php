<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a club trains.
 *
 * A parent choosing a club asks where the sessions are before anything else,
 * and the answer was nowhere on the site. It points at the venue register
 * rather than repeating a pool's name as free text, so a pool that is renamed
 * or closed is renamed or closed everywhere at once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->foreignId('home_venue_id')
                ->nullable()
                ->after('longitude')
                ->constrained('venues')
                ->nullOnDelete();

            // The sessions themselves, in the club's own words: which days,
            // which squads, what time. Too varied to model and it changes
            // every term, so it is prose the club keeps up to date.
            $table->text('training_times')->nullable()->after('home_venue_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('home_venue_id');
            $table->dropColumn('training_times');
        });
    }
};

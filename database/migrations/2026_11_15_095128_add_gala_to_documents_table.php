<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Results arrive as a file out of MeetManager rather than being typed in,
     * so a gala needs somewhere to keep the one it was published from.
     *
     * One gala may carry several: a two-day meet is often exported per day,
     * and a corrected reissue has to sit beside the original rather than
     * quietly replace it.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('gala_id')->nullable()->after('season_id')
                ->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('gala_id');
        });
    }
};

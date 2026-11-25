<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which of a club's coaches is the head coach.
 *
 * A club can carry several, and the one a parent or a provincial convenor
 * needs to reach is the head coach. Without this the directory can only list
 * everybody and leave the reader to guess.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coaches', function (Blueprint $table): void {
            $table->boolean('is_head_coach')->default(false)->after('qualification');
        });
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table): void {
            $table->dropColumn('is_head_coach');
        });
    }
};

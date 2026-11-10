<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A relay result belongs to a team rather than to one swimmer, so the member
 * column has to give way and a team column take its place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->foreignId('relay_team_id')
                ->nullable()
                ->after('gala_entry_id')
                ->comment('Set instead of member_id when the swim is a relay')
                ->constrained()
                ->cascadeOnDelete();
        });

        Schema::table('results', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('relay_team_id');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable(false)->change();
        });
    }
};

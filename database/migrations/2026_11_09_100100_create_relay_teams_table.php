<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relay_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gala_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gala_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->comment('Club or province the team swims for')->constrained();
            $table->string('name')->comment('e.g. Harare A');
            $table->string('letter', 2)->default('A');
            $table->unsignedInteger('entry_time_ms')->nullable();
            $table->unsignedSmallInteger('heat')->nullable();
            $table->unsignedTinyInteger('lane')->nullable();
            $table->string('status', 24)->default('entered')->index();
            $table->timestamps();

            $table->unique(['gala_event_id', 'organization_id', 'letter']);
        });

        Schema::create('relay_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('relay_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('leg');
            $table->string('stroke', 24)->nullable();
            $table->boolean('is_borrowed')->default(false)
                ->comment('Borrowed swimmers may win medals but score no points');
            $table->timestamps();

            $table->unique(['relay_team_id', 'leg']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relay_team_members');
        Schema::dropIfExists('relay_teams');
    }
};

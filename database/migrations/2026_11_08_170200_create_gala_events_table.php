<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gala_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gala_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gala_session_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('event_number');
            $table->string('gender', 16)->comment('girls, boys, mixed');
            $table->string('age_group')->comment('Label shown on the programme, e.g. 11/12 or 15 & Over');
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->string('stroke', 24)->comment('freestyle, backstroke, breaststroke, butterfly, individual_medley, medley_relay, freestyle_relay');
            $table->unsignedSmallInteger('distance');
            $table->string('round', 24)->default('timed_final')->comment('heats, final, timed_final');
            $table->boolean('is_relay')->default(false);
            $table->unsignedTinyInteger('relay_legs')->nullable();
            $table->unsignedInteger('qualifying_time_ms')->nullable();
            $table->unsignedInteger('cutoff_time_ms')->nullable();
            $table->timestamps();

            $table->unique(['gala_id', 'event_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gala_events');
    }
};

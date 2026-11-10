<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_accreditations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_official_id')->constrained()->cascadeOnDelete();
            $table->string('category', 48)->index()->comment('timekeeper, chief_timekeeper, inspector_of_turns, judge_of_stroke, starter, referee, announcer, control_room_supervisor');
            $table->string('grade', 24)->index()->comment('learner, provincial, national, regional');
            $table->string('status', 24)->default('in_progress')->index();
            $table->unsignedSmallInteger('learner_sessions_completed')->default(0);
            $table->date('clinic_attended_on')->nullable();
            $table->date('exam_passed_on')->nullable();
            $table->date('attained_on')->nullable();
            $table->date('expires_on')->nullable()->comment('Lapses after two seasons without officiating at this level');
            $table->foreignId('ratified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ratified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['technical_official_id', 'category', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_accreditations');
    }
};

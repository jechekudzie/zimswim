<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technical_official_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 24)->default('in_progress')->index()
                ->comment('in_progress, theory_passed, awaiting_practical, completed, withdrawn');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('theory_passed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('practical_sessions_completed')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'user_id']);
        });

        Schema::create('lesson_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_enrolment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['course_enrolment_id', 'course_lesson_id'], 'lesson_completions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_completions');
        Schema::dropIfExists('course_enrolments');
    }
};

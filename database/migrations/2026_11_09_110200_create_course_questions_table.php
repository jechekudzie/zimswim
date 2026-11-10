<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_lesson_id')->nullable()
                ->comment('Set when the question is a lesson quiz rather than exam bank only')
                ->constrained()->nullOnDelete();
            $table->text('prompt');
            $table->string('type', 24)->default('single')->comment('single, multiple, true_false');
            $table->text('explanation')->nullable();
            $table->string('rule_reference')->nullable()->comment('World Aquatics rule the question tests');
            $table->boolean('in_exam_bank')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['course_id', 'is_active']);
        });

        Schema::create('course_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_question_id')->constrained()->cascadeOnDelete();
            $table->text('label');
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_question_options');
        Schema::dropIfExists('course_questions');
    }
};

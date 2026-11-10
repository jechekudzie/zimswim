<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('official_category', 48)->nullable()->index()
                ->comment('The officiating category this course leads to');
            $table->string('awards_grade', 24)->nullable()
                ->comment('Grade proposed on passing, pending technical committee ratification');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('cover_image_path')->nullable();

            $table->unsignedTinyInteger('pass_mark')->default(80)->comment('Percentage needed to pass the exam');
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->unsignedSmallInteger('exam_question_count')->default(20)
                ->comment('Questions drawn at random from the bank for each attempt');
            $table->unsignedSmallInteger('exam_minutes')->default(45);
            $table->unsignedTinyInteger('practical_sessions_required')->default(6);
            $table->unsignedTinyInteger('minimum_age')->nullable();

            $table->decimal('fee', 10, 2)->default(0);
            $table->boolean('free_for_registered_parents')->default(true);
            $table->boolean('is_published')->default(false)->index();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};

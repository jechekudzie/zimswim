<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_official_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gala_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gala_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 48)->comment('Duty worked at this session');
            $table->string('grade_worked', 24)->nullable();
            $table->boolean('as_learner')->default(false);
            $table->date('worked_on');
            $table->foreignId('signed_off_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_off_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['technical_official_id', 'worked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_sessions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Somebody putting their name down for a clinic or an exam.
 *
 * Open to anybody, with or without an account: the people a clinic most needs
 * to reach are the ones who are not on the register yet. Where the person
 * does have an account or a place on the register, that is recorded so the
 * host can see who is already an official.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_training_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('official_training_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technical_official_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            // Typed rather than chosen from the register: somebody attending
            // their first clinic may not be attached to a club yet.
            $table->string('club_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('registered_at');
            $table->timestamps();

            // One place per person. A second attempt updates the first rather
            // than taking a seat twice.
            $table->unique(['official_training_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_training_registrations');
    }
};

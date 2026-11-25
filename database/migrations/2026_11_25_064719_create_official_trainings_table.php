<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clinics, exam sittings and refreshers on the officials' calendar.
 *
 * The federation's complaint was concrete: the last timekeeping clinic was
 * advertised through a link that turned people away. Somebody who wants to
 * learn to officiate should be able to find the next date and put their name
 * down, so attendance is open by default and a cap is something a host has to
 * set deliberately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_trainings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('kind', 24);
            // Null where a clinic covers several categories at once, which is
            // how a province usually runs one.
            $table->string('category', 40)->nullable();
            $table->text('description')->nullable();
            $table->date('held_on');
            $table->date('ends_on')->nullable();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            // Where the venue register has no row for it: a school hall, a
            // club house, or an online sitting.
            $table->string('location')->nullable();
            $table->boolean('is_online')->default(false);
            // Null means no cap, which is the default and the point.
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'held_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_trainings');
    }
};

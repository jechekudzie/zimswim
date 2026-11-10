<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('verification_code', 24)->unique()
                ->comment('Printed as a QR code so anyone can confirm the level and expiry');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_enrolment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technical_official_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('official_accreditation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('holder_name');
            $table->string('title');
            $table->string('category', 48)->nullable();
            $table->string('grade', 24)->nullable();
            $table->date('issued_on');
            $table->date('expires_on')->nullable();
            $table->string('issued_by')->nullable();
            $table->string('status', 24)->default('valid')->index()->comment('valid, expired, revoked');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};

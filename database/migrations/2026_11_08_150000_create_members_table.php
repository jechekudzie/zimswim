<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('membership_number', 24)->unique()->comment('ZimSwim number used on every entry and result');
            $table->foreignId('organization_id')->comment('Club the swimmer is registered with')->constrained();
            $table->foreignId('province_organization_id')->nullable()->comment('Declared sporting province')->constrained('organizations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->comment('Set for swimmers 18 and over who log in themselves')->constrained()->nullOnDelete();
            $table->foreignId('guardian_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('gender', 16);
            $table->string('status', 24)->default('pending')->index();
            $table->string('school')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('medical_notes')->nullable();
            $table->timestamp('consent_given_at')->nullable()->comment('Guardian consent under the Cyber and Data Protection Act');
            $table->string('consent_given_by')->nullable();
            $table->boolean('photo_consent')->default(false);
            $table->string('photo_path')->nullable();
            $table->date('joined_on')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

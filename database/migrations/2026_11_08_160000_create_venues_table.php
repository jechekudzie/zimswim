<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('province_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('managed_by_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('owner_type', 24)->default('council')->comment('council, school, club, private');
            $table->string('owner_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->unsignedSmallInteger('pool_length')->nullable()->comment('Metres: 25 or 50');
            $table->unsignedTinyInteger('lanes')->nullable();
            $table->decimal('depth_shallow', 4, 2)->nullable();
            $table->decimal('depth_deep', 4, 2)->nullable();
            $table->boolean('starting_blocks')->default(false);
            $table->boolean('backstroke_ledges')->default(false);
            $table->boolean('backstroke_flags')->default(false);
            $table->boolean('electronic_timing')->default(false);
            $table->boolean('scoreboard')->default(false);
            $table->boolean('warmup_pool')->default(false);
            $table->unsignedInteger('seating_capacity')->nullable();
            $table->boolean('changing_rooms')->default(false);
            $table->boolean('parking')->default(false);
            $table->boolean('power_backup')->default(false);

            $table->string('grade', 24)->default('club_development')->index()->comment('national, provincial, club_development');
            $table->string('water_quality')->nullable();
            $table->date('last_inspected_on')->nullable();
            $table->date('season_opens_on')->nullable();
            $table->date('season_closes_on')->nullable();
            $table->string('condition_notes')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};

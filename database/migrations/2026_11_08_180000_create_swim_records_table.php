<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swim_records', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 24)->default('national')->index()->comment('national or provincial');
            $table->foreignId('organization_id')->nullable()->comment('Province holding the record when scope is provincial')->constrained()->nullOnDelete();
            $table->string('course', 8)->comment('LCM or SCM');
            $table->string('gender', 16);
            $table->string('age_group');
            $table->string('stroke', 24);
            $table->unsignedSmallInteger('distance');
            $table->boolean('is_relay')->default(false);

            $table->unsignedInteger('time_ms');
            $table->string('holder_name')->comment('Kept as text so historic records survive without a member row');
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('club_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('venue_name')->nullable();
            $table->date('set_on')->nullable();
            $table->foreignId('gala_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('result_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 24)->default('ratified')->index()->comment('pending, ratified, superseded, rejected');
            $table->foreignId('ratified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ratified_at')->nullable();
            $table->string('ratifying_body')->nullable();
            $table->timestamps();

            $table->index(['scope', 'course', 'gender', 'age_group', 'stroke', 'distance'], 'swim_records_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swim_records');
    }
};

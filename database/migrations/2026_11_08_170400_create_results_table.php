<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gala_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gala_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gala_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained();
            $table->unsignedInteger('time_ms')->nullable();
            $table->unsignedSmallInteger('place')->nullable();
            $table->unsignedSmallInteger('points')->nullable();
            $table->unsignedSmallInteger('heat')->nullable();
            $table->unsignedTinyInteger('lane')->nullable();
            $table->string('status', 24)->default('official')->comment('official, dq, dns, dnf, provisional');
            $table->string('dq_code', 24)->nullable()->comment('World Aquatics rule reference, e.g. 7.4');
            $table->string('dq_reason')->nullable();
            $table->boolean('is_personal_best')->default(false);
            $table->boolean('record_flagged')->default(false)->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_id', 'time_ms']);
            $table->index(['gala_event_id', 'place']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};

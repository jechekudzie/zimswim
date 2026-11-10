<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gala_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gala_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gala_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->comment('Club the entry is submitted by')->constrained();
            $table->unsignedInteger('entry_time_ms')->nullable();
            $table->unsignedSmallInteger('heat')->nullable();
            $table->unsignedTinyInteger('lane')->nullable();
            $table->string('status', 24)->default('entered')->index()->comment('entered, confirmed, withdrawn, scratched, no_show');
            $table->boolean('is_late_entry')->default(false);
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['gala_event_id', 'member_id']);
            $table->index(['gala_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gala_entries');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gala_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gala_id')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('e.g. Saturday AM');
            $table->unsignedTinyInteger('position')->default(1);
            $table->date('held_on');
            $table->time('warmup_at')->nullable();
            $table->time('starts_at')->nullable();
            $table->timestamps();

            $table->index(['gala_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gala_sessions');
    }
};

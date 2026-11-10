<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gala_officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gala_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technical_official_id')->constrained()->cascadeOnDelete();
            $table->string('role', 48);
            $table->string('status', 24)->default('invited')->comment('invited, accepted, declined, confirmed');
            $table->timestamps();

            $table->unique(['gala_id', 'technical_official_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gala_officials');
    }
};

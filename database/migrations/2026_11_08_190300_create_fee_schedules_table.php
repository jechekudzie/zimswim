<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 48)->index();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('unit')->nullable()->comment('e.g. per athlete, per club');
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['season_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_schedules');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->comment('Club registered with for this season')->constrained();
            $table->string('status', 24)->default('pending')->index();
            $table->decimal('capitation_amount', 10, 2)->default(0);
            $table->timestamp('capitation_paid_at')->nullable();
            $table->decimal('aquatics_amount', 10, 2)->default(0);
            $table->timestamp('aquatics_paid_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_registrations');
    }
};

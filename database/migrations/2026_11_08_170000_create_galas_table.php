<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('season_id')->constrained();
            $table->foreignId('host_organization_id')->constrained('organizations');
            $table->foreignId('sanctioning_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level', 24)->index()->comment('club, provincial, national, invitational, international');
            $table->string('course', 8)->default('SCM')->comment('LCM or SCM');
            $table->string('status', 24)->default('draft')->index();
            $table->date('starts_on')->index();
            $table->date('ends_on');
            $table->date('age_up_date')->nullable()->comment('Age as at this date decides the age group');
            $table->timestamp('entries_open_at')->nullable();
            $table->timestamp('entries_close_at')->nullable();
            $table->date('entry_corrections_deadline')->nullable();
            $table->decimal('entry_fee', 10, 2)->default(0);
            $table->decimal('late_entry_fee', 10, 2)->default(0);
            $table->decimal('late_entry_event_fee', 10, 2)->default(0);
            $table->decimal('no_show_fine', 10, 2)->default(0);
            $table->decimal('appeal_deposit', 10, 2)->default(0);
            $table->string('eligible_ages')->nullable();
            $table->text('description')->nullable();
            $table->text('entry_instructions')->nullable();
            $table->string('entries_email')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('qualifying_times_apply')->default(false);
            $table->boolean('is_published')->default(false)->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sanctioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sanctioned_at')->nullable();
            $table->text('sanction_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['season_id', 'level', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galas');
    }
};

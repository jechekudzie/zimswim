<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Somebody asking to be let onto the officials pathway.
     *
     * The row stands on its own rather than hanging off a user: an applicant
     * has no account until the technical committee approves them, and a
     * declined application still has to be answerable for afterwards.
     */
    public function up(): void
    {
        Schema::create('official_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique()->comment('Quoted by the applicant when they ask where it stands');

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->index();
            $table->string('phone', 40);
            $table->date('date_of_birth');

            $table->string('category', 32)->index()->comment('OfficialCategory they are applying to work in');
            $table->text('motivation')->nullable();

            // Club of domicile, when they already belong to one.
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('club_name_given')->nullable()->comment('What they typed when their club is not on the register yet');
            $table->string('membership_number', 32)->nullable();

            $table->string('status', 24)->default('submitted')->index()
                ->comment('submitted, under_review, approved, declined, withdrawn');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // The account opened for them on approval.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technical_official_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            /*
             * The review queue is read far more often than the archive, and
             * always in the order things arrived.
             */
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_applications');
    }
};

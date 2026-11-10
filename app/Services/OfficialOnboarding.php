<?php

namespace App\Services;

use App\Enums\OfficialApplicationStatus;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Models\OfficialApplication;
use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Notifications\OfficialApplicationApproved;
use App\Notifications\OfficialApplicationDeclined;
use App\Notifications\OfficialApplicationReceived;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Taking somebody from "I would like to help at galas" to a learner with an
 * account, a place on the register, and a course to work through.
 *
 * The three things approval has to create — an account, a technical official
 * record, and an enrolment — are created together or not at all, because a
 * half-approved applicant is somebody the committee believes it has admitted
 * and who cannot sign in.
 */
class OfficialOnboarding
{
    /**
     * Record an application and acknowledge it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function apply(array $attributes): OfficialApplication
    {
        $application = OfficialApplication::create([
            ...$attributes,
            'reference' => OfficialApplication::newReference(),
            'status' => OfficialApplicationStatus::Submitted,
        ]);

        // The applicant has no account yet, so the acknowledgement goes to
        // the address they typed rather than to a notifiable model.
        Notification::route('mail', $application->email)
            ->notify(new OfficialApplicationReceived($application));

        return $application;
    }

    /**
     * Admit the applicant: open an account, put them on the register, and
     * enrol them in the course for the category they asked for.
     */
    public function approve(OfficialApplication $application, User $reviewer, ?string $notes = null): OfficialApplication
    {
        if (! $application->status->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'This application has already been '.Str::lower($application->status->label()).'.',
            ]);
        }

        if ($application->organization_id === null) {
            throw ValidationException::withMessages([
                'organization_id' => 'Attach the applicant to a club before approving. An official is registered through the club they are domiciled in.',
            ]);
        }

        [$user, $official, $course, $accountIsNew] = DB::transaction(function () use ($application, $reviewer, $notes): array {
            $accountIsNew = User::where('email', $application->email)->doesntExist();
            $user = $this->account($application);
            $official = $this->registerOfficial($application, $user);
            $course = $this->enrol($application, $user, $official);

            $application->update([
                'status' => OfficialApplicationStatus::Approved,
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
                'review_notes' => $notes,
                'user_id' => $user->getKey(),
                'technical_official_id' => $official->getKey(),
            ]);

            return [$user, $official, $course, $accountIsNew];
        });

        /*
         * Sent after the transaction commits, so a rolled-back approval can
         * never post an invitation. Somebody who already had an account is
         * not sent a password link they do not need.
         */
        if ($accountIsNew) {
            $user->notify(new AccountCreated(
                $application->club->name ?? 'Zimbabwe Swimming',
                Roles::label(Roles::GALA_OFFICIAL),
            ));
        }

        $user->notify(new OfficialApplicationApproved($application->fresh(), $course));

        return $application->fresh();
    }

    /**
     * Turn the applicant down, with the reason they are owed.
     */
    public function decline(OfficialApplication $application, User $reviewer, string $reason): OfficialApplication
    {
        if (! $application->status->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'This application has already been '.Str::lower($application->status->label()).'.',
            ]);
        }

        $application->update([
            'status' => OfficialApplicationStatus::Declined,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'review_notes' => $reason,
        ]);

        Notification::route('mail', $application->email)
            ->notify(new OfficialApplicationDeclined($application->fresh()));

        return $application->fresh();
    }

    /**
     * The account they will sign in with.
     *
     * Somebody who already has one — a parent, a coach, a swimmer — keeps it
     * and is simply given the official's role at their club, rather than
     * ending up with two logins for the same person.
     */
    private function account(OfficialApplication $application): User
    {
        $user = User::where('email', $application->email)->first();

        if ($user === null) {
            $user = User::create([
                'name' => $application->fullName(),
                'email' => $application->email,
                'phone' => $application->phone,
                'password' => Hash::make(Str::password(16)),
                'current_organization_id' => $application->organization_id,
                'is_active' => true,
            ]);
        }

        if (! $user->organizations()->whereKey($application->organization_id)->exists()) {
            $user->organizations()->attach($application->organization_id, [
                'is_default' => $user->organizations()->count() === 0,
            ]);
        }

        setPermissionsTeamId($application->organization_id);
        $user->assignRole(Roles::GALA_OFFICIAL);
        setPermissionsTeamId(null);

        return $user;
    }

    /**
     * Their row on the officials register.
     */
    private function registerOfficial(OfficialApplication $application, User $user): TechnicalOfficial
    {
        $existing = TechnicalOfficial::query()
            ->where('user_id', $user->getKey())
            ->orWhere('email', $application->email)
            ->first();

        if ($existing !== null) {
            $existing->update(['user_id' => $user->getKey()]);

            return $existing;
        }

        return TechnicalOfficial::create([
            'registration_number' => $this->registrationNumber(),
            'user_id' => $user->getKey(),
            'organization_id' => $application->organization_id,
            'province_organization_id' => $application->province_organization_id,
            'first_name' => $application->first_name,
            'last_name' => $application->last_name,
            'email' => $application->email,
            'phone' => $application->phone,
            'date_of_birth' => $application->date_of_birth,
            // A learner is on the register but holds nothing yet: the grade
            // arrives with the first accreditation, after the exam.
            'status' => 'learner',
        ]);
    }

    /**
     * Put them on the course for the category they applied for.
     *
     * A category with no published course yet still produces an approved
     * official — they can be taught at a clinic and signed off poolside —
     * so a missing course is a null here, not a failure.
     */
    private function enrol(OfficialApplication $application, User $user, TechnicalOfficial $official): ?Course
    {
        $course = Course::query()
            ->published()
            ->where('official_category', $application->category)
            ->first();

        if ($course === null) {
            return null;
        }

        CourseEnrolment::firstOrCreate(
            ['course_id' => $course->getKey(), 'user_id' => $user->getKey()],
            [
                'technical_official_id' => $official->getKey(),
                'organization_id' => $application->organization_id,
                'enrolled_at' => now(),
            ],
        );

        return $course;
    }

    /**
     * The next number on the register.
     *
     * Taken from the highest in use rather than from a count, so numbers are
     * never reissued after a record is removed.
     */
    private function registrationNumber(): string
    {
        $highest = TechnicalOfficial::query()
            ->withTrashed()
            ->where('registration_number', 'like', 'TO-%')
            ->orderByRaw('length(registration_number) desc, registration_number desc')
            ->value('registration_number');

        $next = $highest === null ? 1 : ((int) Str::after($highest, 'TO-')) + 1;

        return sprintf('TO-%04d', $next);
    }
}

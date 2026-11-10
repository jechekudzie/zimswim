<?php

namespace Database\Seeders;

use App\Enums\AccreditationStatus;
use App\Enums\EnrolmentStatus;
use App\Enums\OfficialCategory;
use App\Enums\OfficialGrade;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Models\ExamAttempt;
use App\Models\LessonCompletion;
use App\Models\OfficialAccreditation;
use App\Models\Organization;
use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Four officials at four different points on the pathway, so the portal can
 * be walked through without waiting a season for somebody to get there.
 *
 * They deliberately include failure. An exam attempt that did not reach the
 * pass mark is the ordinary case — three attempts are allowed because people
 * need them — and a portal that only ever shows a clean run is a portal
 * nobody has checked against a real record.
 *
 * Every account shares the development password set below, and none of this
 * belongs on a live server.
 */
class OfficialJourneySeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        $club = Organization::where('slug', 'les-brown-swimming-club')->first()
            ?? Organization::where('type', 'club')->first();

        if ($club === null) {
            $this->command?->warn('No club on the register — skipping official journeys.');

            return;
        }

        $timekeeper = Course::where('official_category', OfficialCategory::Timekeeper)->first();
        $judge = Course::where('official_category', OfficialCategory::JudgeOfStroke)->first();

        if ($timekeeper === null) {
            $this->command?->warn('No timekeeper course — run the AcademySeeder first.');

            return;
        }

        // Just started: enrolled, two lessons in, no exam yet.
        $this->official('Rudo', 'Chiwara', $club, function (TechnicalOfficial $official, User $user) use ($timekeeper): void {
            $enrolment = $this->enrol($timekeeper, $user, $official);
            $this->completeLessons($enrolment, 2);
        });

        // Failed once, passed on the second attempt, practicals outstanding.
        $this->official('Farai', 'Nyoni', $club, function (TechnicalOfficial $official, User $user) use ($timekeeper): void {
            $enrolment = $this->enrol($timekeeper, $user, $official);
            $this->completeLessons($enrolment);

            $this->attempt($enrolment, 1, 60, 6, false, 34);
            $this->attempt($enrolment, 2, 90, 9, true, 20);

            $enrolment->update([
                'status' => EnrolmentStatus::AwaitingPractical,
                'theory_passed_at' => now()->subDays(20),
                'practical_sessions_completed' => 2,
            ]);
        });

        // Out of attempts: three sat, none passed.
        $this->official('Tapiwa', 'Moyo', $club, function (TechnicalOfficial $official, User $user) use ($timekeeper): void {
            $enrolment = $this->enrol($timekeeper, $user, $official);
            $this->completeLessons($enrolment);

            $this->attempt($enrolment, 1, 50, 5, false, 70);
            $this->attempt($enrolment, 2, 70, 7, false, 45);
            $this->attempt($enrolment, 3, 70, 7, false, 15);
        });

        // Finished: passed, practicals signed off, certificate in hand.
        $this->official('Nomsa', 'Sibanda', $club, function (TechnicalOfficial $official, User $user) use ($timekeeper, $judge): void {
            $enrolment = $this->enrol($timekeeper, $user, $official);
            $this->completeLessons($enrolment);

            $this->attempt($enrolment, 1, 70, 7, false, 180);
            $this->attempt($enrolment, 2, 100, 10, true, 150);

            $enrolment->update([
                'status' => EnrolmentStatus::Completed,
                'theory_passed_at' => now()->subDays(150),
                'completed_at' => now()->subDays(120),
                'practical_sessions_completed' => $timekeeper->practical_sessions_required,
            ]);

            OfficialAccreditation::updateOrCreate(
                [
                    'technical_official_id' => $official->getKey(),
                    'category' => OfficialCategory::Timekeeper,
                ],
                [
                    'grade' => OfficialGrade::Provincial,
                    'status' => AccreditationStatus::Active,
                    'learner_sessions_completed' => $timekeeper->practical_sessions_required,
                    'exam_passed_on' => now()->subDays(150)->toDateString(),
                    'attained_on' => now()->subDays(120)->toDateString(),
                    'expires_on' => now()->addYears(2)->toDateString(),
                ],
            );

            Certificate::updateOrCreate(
                ['course_enrolment_id' => $enrolment->getKey()],
                [
                    'verification_code' => 'ZSW-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)),
                    'user_id' => $user->getKey(),
                    'course_id' => $timekeeper->getKey(),
                    'technical_official_id' => $official->getKey(),
                    'holder_name' => $official->fullName(),
                    'title' => $timekeeper->title,
                    'category' => OfficialCategory::Timekeeper->value,
                    'grade' => OfficialGrade::Provincial->value,
                    'issued_on' => now()->subDays(120)->toDateString(),
                    'expires_on' => now()->addYears(2)->toDateString(),
                    'issued_by' => 'Zimbabwe Swimming',
                    'status' => 'valid',
                ],
            );

            // Now working towards the next category up, one lesson in.
            if ($judge !== null) {
                $next = $this->enrol($judge, $user, $official);
                $this->completeLessons($next, 1);
            }
        });

        $this->command?->info('Four official journeys seeded. Password for all: '.self::PASSWORD);
    }

    /**
     * An official with an account, and whatever history the callback gives
     * them.
     *
     * @param  callable(TechnicalOfficial, User): void  $history
     */
    private function official(string $first, string $last, Organization $club, callable $history): void
    {
        $email = Str::lower($first.'.'.$last).'@example.test';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $first.' '.$last,
                'password' => Hash::make(self::PASSWORD),
                'current_organization_id' => $club->getKey(),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        if (! $user->organizations()->whereKey($club->getKey())->exists()) {
            $user->organizations()->attach($club->getKey(), ['is_default' => true]);
        }

        setPermissionsTeamId($club->getKey());
        $user->assignRole(Roles::GALA_OFFICIAL);
        setPermissionsTeamId(null);

        $official = TechnicalOfficial::updateOrCreate(
            ['email' => $email],
            [
                'registration_number' => $this->registrationNumber(),
                'user_id' => $user->getKey(),
                'organization_id' => $club->getKey(),
                'province_organization_id' => $club->parent_id,
                'first_name' => $first,
                'last_name' => $last,
                'phone' => '+263 77 '.random_int(100, 999).' '.random_int(1000, 9999),
                'date_of_birth' => now()->subYears(random_int(22, 48))->toDateString(),
                'status' => 'learner',
            ],
        );

        $history($official, $user);
    }

    private function enrol(Course $course, User $user, TechnicalOfficial $official): CourseEnrolment
    {
        return CourseEnrolment::updateOrCreate(
            ['course_id' => $course->getKey(), 'user_id' => $user->getKey()],
            [
                'technical_official_id' => $official->getKey(),
                'organization_id' => $official->organization_id,
                'enrolled_at' => now()->subMonths(8),
                'status' => EnrolmentStatus::InProgress,
            ],
        );
    }

    /**
     * Mark lessons done, all of them unless a number is given.
     */
    private function completeLessons(CourseEnrolment $enrolment, ?int $limit = null): void
    {
        $lessons = $enrolment->course->lessons()
            ->where('is_published', true)
            ->orderBy('position')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();

        foreach ($lessons as $lesson) {
            LessonCompletion::updateOrCreate([
                'course_enrolment_id' => $enrolment->getKey(),
                'course_lesson_id' => $lesson->getKey(),
            ], ['completed_at' => now()->subMonths(6)]);
        }
    }

    private function attempt(
        CourseEnrolment $enrolment,
        int $number,
        int $score,
        int $correct,
        bool $passed,
        int $daysAgo,
    ): void {
        ExamAttempt::updateOrCreate(
            ['course_enrolment_id' => $enrolment->getKey(), 'attempt_number' => $number],
            [
                'course_id' => $enrolment->course_id,
                'user_id' => $enrolment->user_id,
                'status' => 'submitted',
                'question_count' => $enrolment->course->exam_question_count,
                'correct_count' => $correct,
                'score' => $score,
                'passed' => $passed,
                'started_at' => now()->subDays($daysAgo),
                'expires_at' => now()->subDays($daysAgo)->addMinutes($enrolment->course->exam_minutes),
                'submitted_at' => now()->subDays($daysAgo)->addMinutes(22),
            ],
        );
    }

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

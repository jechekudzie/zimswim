<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CourseEnrolment;
use App\Models\OfficialAccreditation;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Issues the digital certificate that proves a level and its expiry.
 */
class CertificateIssuer
{
    public function issue(CourseEnrolment $enrolment, ?OfficialAccreditation $accreditation = null): Certificate
    {
        if (! $enrolment->isReadyForCertificate()) {
            throw ValidationException::withMessages([
                'certificate' => 'The exam must be passed and every practical session signed off first.',
            ]);
        }

        $existing = Certificate::query()
            ->where('course_enrolment_id', $enrolment->getKey())
            ->valid()
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $course = $enrolment->course;
        $expiry = $accreditation === null ? now()->addYears(2) : ($accreditation->expires_on ?? now()->addYears(2));

        return Certificate::create([
            'verification_code' => $this->code(),
            'user_id' => $enrolment->user_id,
            'course_id' => $course->getKey(),
            'course_enrolment_id' => $enrolment->getKey(),
            'technical_official_id' => $enrolment->technical_official_id,
            'official_accreditation_id' => $accreditation?->getKey(),
            'holder_name' => $enrolment->user->displayName(),
            'title' => $course->title,
            'category' => $course->official_category?->value,
            'grade' => $course->awards_grade?->value,
            'issued_on' => now()->toDateString(),
            'expires_on' => $expiry->toDateString(),
            'issued_by' => 'Zimbabwe Swimming',
            'status' => 'valid',
        ]);
    }

    /**
     * A short code that is quick to read aloud and hard to guess.
     */
    private function code(): string
    {
        do {
            $code = 'ZSW-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
        } while (Certificate::where('verification_code', $code)->exists());

        return $code;
    }
}

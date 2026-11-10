<?php

namespace App\Http\Controllers\Academy;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Services\CertificateIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function __construct(private readonly CertificateIssuer $issuer) {}

    /**
     * Anyone can confirm a certificate from its QR code, without logging in.
     */
    public function verify(Certificate $certificate): Response
    {
        return Inertia::render('public/certificate', [
            'certificate' => [
                'code' => $certificate->verification_code,
                'holder_name' => $certificate->holder_name,
                'title' => $certificate->title,
                'grade' => $certificate->grade,
                'issued_on' => $certificate->issued_on->toDateString(),
                'expires_on' => $certificate->expires_on?->toDateString(),
                'issued_by' => $certificate->issued_by,
                'status' => $certificate->status,
                'valid' => $certificate->isValid(),
                'expired' => $certificate->hasExpired(),
            ],
        ]);
    }

    /**
     * Issued by the technical committee once theory and practicals are done.
     */
    public function issue(Request $request, Course $course, CourseEnrolment $enrolment): RedirectResponse
    {
        $this->authorize('certify', Course::class);

        abort_unless($enrolment->course_id === $course->getKey(), 404);

        $certificate = $this->issuer->issue($enrolment);

        return back()->with(
            'success',
            'Certificate '.$certificate->verification_code.' issued to '.$certificate->holder_name.'.',
        );
    }
}

<?php

namespace App\Http\Controllers\Academy;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrolment;
use App\Services\CertificateDocument;
use App\Services\CertificateIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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
     * The printable certificate.
     *
     * It opens in the browser's own PDF viewer rather than dropping into
     * Downloads: somebody checking an official on a pool deck wants to look
     * at it, not collect it, and the viewer already carries save and print.
     * `?download=1` forces the save for anybody who did want the file.
     *
     * Open to the holder and to anybody who may manage officials — a
     * convenor reprinting one for somebody whose laptop died is the ordinary
     * case, not an exception. Everybody else uses the public verification
     * page, which answers the only question an outsider should be asking.
     */
    public function pdf(Request $request, Certificate $certificate, CertificateDocument $document): HttpResponse
    {
        abort_unless(
            $certificate->user_id === $request->user()->getKey()
                || Gate::allows('certify', Course::class),
            403,
        );

        $pdf = $document->render($certificate);
        $filename = $document->filename($certificate);

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
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

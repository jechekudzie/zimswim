<?php

namespace App\Services;

use App\Enums\OfficialGrade;
use App\Models\Certificate;
use App\Support\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWrapper;
use Illuminate\Support\Str;

/**
 * Renders a certificate as the printable document somebody takes to a pool
 * deck.
 *
 * Everything on the page is embedded — the logo as a data URI, the QR code
 * as inline SVG — so the PDF is the same document whether it is printed the
 * day it is issued or found on a laptop five years later with no network.
 */
class CertificateDocument
{
    public function render(Certificate $certificate): PdfWrapper
    {
        return Pdf::loadView('certificates.official', [
            'certificate' => $certificate,
            'grade' => $this->grade($certificate),
            'logo' => $this->logo(),
            // The code points at the public verification page, which is the
            // only thing that can say whether the certificate still stands:
            // the paper cannot know it was revoked yesterday.
            'qr' => QrCode::imageTag(route('certificates.verify', $certificate), '22mm'),
            'signatory' => 'Zimbabwe Swimming',
            'signatoryRole' => 'Chairperson, Technical Committee',
        ])->setPaper('a4', 'landscape');
    }

    /**
     * What the browser should call the file once it lands in Downloads.
     */
    public function filename(Certificate $certificate): string
    {
        return Str::slug($certificate->holder_name.' '.$certificate->title)
            .'-'.$certificate->verification_code.'.pdf';
    }

    private function grade(Certificate $certificate): ?string
    {
        if ($certificate->grade === null) {
            return null;
        }

        return OfficialGrade::tryFrom($certificate->grade)?->label() ?? $certificate->grade;
    }

    /**
     * The logo as a data URI, or null where the file is missing: a
     * certificate without its crest is still a certificate, and a missing
     * asset must not be the reason somebody cannot print one.
     */
    private function logo(): ?string
    {
        $path = public_path('brand/logo.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}

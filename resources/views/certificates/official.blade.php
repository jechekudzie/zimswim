<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $certificate->title }} — {{ $certificate->holder_name }}</title>
<style>
    /*
     * A4 portrait, built for DomPDF: no flex, no grid, no web fonts, and no
     * opacity — the washes are flat tints instead, because DomPDF renders
     * alpha unevenly between the screen preview and a printer.
     */
    @page { margin: 0; size: A4 portrait; }

    body {
        margin: 0;
        width: 210mm;
        height: 297mm;
        position: relative;
        font-family: 'DejaVu Serif', 'Times New Roman', serif;
        color: #0B2A4A;
        background: #F6F4EC;
    }

    /* The double rule that makes a certificate read as a certificate. */
    .border-outer {
        position: absolute;
        top: 9mm; left: 9mm; right: 9mm; bottom: 9mm;
        border: 1.6mm double #B8912F;
        background: #FFFDF6;
    }
    .border-inner {
        position: absolute;
        top: 13mm; left: 13mm; right: 13mm; bottom: 13mm;
        border: 0.3mm solid #E0CF9E;
    }

    /*
     * Barely off the panel it sits on. A watermark is texture: the moment
     * it is legible it is competing with the holder's name, which is the
     * one thing on the page that has to be read from across a room.
     */
    .watermark {
        position: absolute;
        top: 196mm; left: 0; right: 0;
        text-align: center;
        font-size: 34pt;
        font-weight: bold;
        letter-spacing: 6pt;
        color: #F8F4E8;
    }

    .sheet { position: absolute; top: 22mm; left: 26mm; right: 26mm; text-align: center; }

    .logo { height: 19mm; }

    .federation {
        margin-top: 4mm;
        font-size: 13pt;
        font-weight: bold;
        letter-spacing: 2.2pt;
        color: #0B2A4A;
    }

    .contact { margin-top: 2mm; font-size: 7.5pt; color: #7A6A42; line-height: 1.5; }

    .standard {
        margin-top: 6mm;
        font-size: 9.5pt;
        font-style: italic;
        color: #5B6E80;
    }

    .kind {
        margin-top: 6mm;
        font-size: 17pt;
        font-weight: bold;
        letter-spacing: 2.2pt;
        color: #0B2A4A;
    }
    .kind-rule { width: 54mm; margin: 3mm auto 0 auto; border-top: 0.8mm solid #B8912F; }

    .lede { margin-top: 9mm; font-size: 10pt; color: #6B7B8C; }

    .holder {
        margin-top: 3mm;
        font-size: 27pt;
        font-weight: bold;
        color: #0B2A4A;
    }
    .holder-rule { width: 118mm; margin: 3mm auto 0 auto; border-top: 0.3mm solid #D8C79A; }

    .facts { width: 132mm; margin: 9mm auto 0 auto; border-collapse: separate; border-spacing: 0 2.6mm; }
    .facts td { font-size: 10pt; vertical-align: top; }
    .facts .label { width: 48%; text-align: right; padding-right: 5mm; color: #7A6A42; }
    .facts .value { width: 52%; text-align: left; font-weight: bold; color: #0B2A4A; }

    .award { margin-top: 9mm; font-size: 16pt; font-weight: bold; color: #0A6B38; }
    .grade { margin-top: 2mm; font-size: 10pt; color: #6B7B8C; }

    .expiry { margin-top: 9mm; font-size: 9.5pt; color: #6B7B8C; }
    .expiry strong {
        display: block;
        margin-top: 1.5mm;
        font-size: 13pt;
        color: #B8912F;
        text-decoration: underline;
    }

    /* A positioned div holding a table: DomPDF will not place a table on its own. */
    .foot { position: absolute; bottom: 22mm; left: 26mm; width: 158mm; }
    .foot table { width: 100%; border-collapse: collapse; }
    .foot td { vertical-align: bottom; padding: 0; }

    .sign { width: 84mm; text-align: left; }
    .sign .line { border-top: 0.4mm solid #0B2A4A; width: 66mm; margin-bottom: 1.6mm; }
    .sign .who { font-size: 8.5pt; font-weight: bold; letter-spacing: 0.6pt; }
    .sign .role { font-size: 7.5pt; color: #7A6A42; margin-top: 0.6mm; }

    .verify { width: 34mm; text-align: right; }
    .verify .code {
        margin-top: 1.4mm;
        font-family: 'DejaVu Sans Mono', monospace;
        font-size: 7.5pt;
        color: #0B2A4A;
    }
    .verify .hint { margin-top: 0.6mm; font-size: 6pt; color: #9A8C6A; }

    /* The flag, hairline thin, at the very foot of the frame. */
    .flag { position: absolute; bottom: 13mm; left: 13mm; right: 13mm; height: 1.4mm; }
    .flag span { display: block; float: left; height: 1.4mm; width: 25%; }
    .flag .green { background: #00843D; }
    .flag .gold  { background: #F9C80E; }
    .flag .red   { background: #D62828; }
    .flag .ink   { background: #0B2A4A; }

    .void {
        position: absolute;
        top: 150mm; left: 0; right: 0;
        text-align: center;
        font-size: 40pt;
        font-weight: bold;
        letter-spacing: 9pt;
        color: #F0C9C9;
    }
</style>
</head>
<body>
    <div class="border-outer"></div>
    <div class="border-inner"></div>
    <div class="watermark">ZIMBABWE SWIMMING</div>

    @unless ($certificate->isValid())
        {{-- A reprint of something no longer good has to say so on its face. --}}
        <div class="void">{{ $certificate->hasExpired() ? 'EXPIRED' : 'NOT VALID' }}</div>
    @endunless

    <div class="sheet">
        @if ($logo)
            <img src="{{ $logo }}" alt="" class="logo">
        @endif

        <div class="federation">ZIMBABWE SWIMMING</div>
        <div class="contact">
            The national federation for swimming in Zimbabwe
            @if ($contactEmail)
                <br>{{ $contactEmail }}@if ($contactPhone) &nbsp;&bull;&nbsp; {{ $contactPhone }}@endif
            @endif
        </div>

        <div class="standard">Certification and Grading Standards for Technical Officials</div>

        <div class="kind">CERTIFICATE OF ACCREDITATION</div>
        <div class="kind-rule"></div>

        <div class="lede">This is to certify that</div>
        <div class="holder">{{ $certificate->holder_name }}</div>
        <div class="holder-rule"></div>

        <table class="facts">
            <tr>
                <td class="label">Certificate number</td>
                <td class="value">{{ $certificate->verification_code }}</td>
            </tr>
            @if ($registrationNumber)
                <tr>
                    <td class="label">Officials register number</td>
                    <td class="value">{{ $registrationNumber }}</td>
                </tr>
            @endif
            @if ($club)
                <tr>
                    <td class="label">Club of domicile</td>
                    <td class="value">{{ $club }}</td>
                </tr>
            @endif
        </table>

        <div class="lede" style="margin-top: 8mm">
            having completed the prescribed course of instruction, passed the
            written examination and satisfied the required poolside sessions,
            is accredited as
        </div>

        <div class="award">{{ $certificate->title }}</div>

        @if ($grade)
            <div class="grade">at {{ $grade }} grade</div>
        @endif

        <div class="expiry">
            @if ($certificate->expires_on)
                This accreditation is valid until
                <strong>{{ $certificate->expires_on->format('j F Y') }}</strong>
            @else
                <strong>This accreditation does not expire</strong>
            @endif
        </div>
    </div>

    <div class="foot">
        <table>
            <tr>
                <td class="sign">
                    <div class="line"></div>
                    <div class="who">{{ $signatory }}</div>
                    <div class="role">{{ $signatoryRole }}</div>
                    <div class="role">Issued {{ $certificate->issued_on->format('j F Y') }}</div>
                </td>

                <td class="verify">
                    {!! $qr !!}
                    <div class="code">{{ $certificate->verification_code }}</div>
                    <div class="hint">Scan to confirm</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="flag"><span class="green"></span><span class="gold"></span><span class="red"></span><span class="ink"></span></div>
</body>
</html>

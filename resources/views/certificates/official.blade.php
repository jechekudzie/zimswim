<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $certificate->title }} — {{ $certificate->holder_name }}</title>
<style>
    /*
     * A4 landscape, built for DomPDF: no flexbox, no grid, no web fonts.
     * Everything is placed absolutely off the page edges so the layout does
     * not depend on how the engine collapses margins.
     */
    @page { margin: 0; size: A4 landscape; }

    body {
        margin: 0;
        width: 297mm;
        height: 210mm;
        position: relative;
        font-family: 'DejaVu Sans', sans-serif;
        color: #0B2A4A;
        background: #ffffff;
    }

    /* The flag, as the rule it is on every other Zimbabwe Swimming page. */
    .flag { position: absolute; left: 0; right: 0; height: 5mm; }
    .flag-top { top: 0; }
    .flag-bottom { bottom: 0; }
    .flag span { display: block; float: left; height: 5mm; }
    .flag .green  { width: 25%; background: #00843D; }
    .flag .gold   { width: 25%; background: #F9C80E; }
    .flag .red    { width: 25%; background: #D62828; }
    .flag .ink    { width: 25%; background: #0B2A4A; }

    .frame {
        position: absolute;
        top: 11mm; left: 11mm; right: 11mm; bottom: 11mm;
        border: 0.6mm solid #0B2A4A;
    }

    .sheet { position: absolute; top: 20mm; left: 24mm; right: 24mm; text-align: center; }

    .logo { height: 17mm; }

    .federation {
        margin-top: 3mm;
        font-size: 10pt;
        letter-spacing: 2.6pt;
        color: #33506B;
    }

    .standard { margin-top: 1mm; font-size: 8.5pt; color: #5B7288; }

    .kind {
        margin-top: 8mm;
        font-size: 22pt;
        font-weight: bold;
        letter-spacing: 3.4pt;
        color: #0B2A4A;
    }

    .lede { margin-top: 7mm; font-size: 10.5pt; color: #5B7288; }

    .holder {
        margin-top: 3mm;
        font-size: 30pt;
        font-weight: bold;
        color: #0B2A4A;
    }

    .rule { width: 110mm; margin: 4mm auto 0 auto; border-top: 0.4mm solid #C8D4E0; }

    .body { margin-top: 5mm; font-size: 10.5pt; line-height: 1.55; color: #33506B; }

    .award { margin-top: 5mm; font-size: 17pt; font-weight: bold; color: #00843D; }
    .grade { margin-top: 1.5mm; font-size: 10.5pt; color: #5B7288; }

    /*
     * A table rather than floats: DomPDF resolves a three-cell row at a
     * known width exactly, where floated blocks of different heights ran
     * off the foot of the page.
     */
    .foot { position: absolute; bottom: 17mm; left: 24mm; width: 249mm; }
    .foot table { width: 100%; border-collapse: collapse; }
    .foot td { vertical-align: bottom; padding: 0; }

    .sign { width: 95mm; text-align: left; }
    .sign .line { border-top: 0.4mm solid #0B2A4A; width: 72mm; margin-bottom: 1.5mm; }
    .sign .who { font-size: 9pt; font-weight: bold; letter-spacing: 0.8pt; }
    .sign .role { font-size: 8pt; color: #5B7288; margin-top: 0.6mm; }

    .dates { width: 110mm; text-align: center; font-size: 8pt; color: #5B7288; }
    .dates strong { font-size: 9.5pt; color: #0B2A4A; }
    .dates .pair { display: inline-block; margin: 0 6mm; }

    .verify { width: 34mm; text-align: center; }
    .verify .code {
        margin-top: 1.5mm;
        font-family: 'DejaVu Sans Mono', monospace;
        font-size: 8pt;
        color: #0B2A4A;
    }
    .verify .hint { margin-top: 0.8mm; font-size: 6.5pt; color: #7A8CA0; line-height: 1.3; }

    .void {
        position: absolute;
        top: 95mm; left: 0; right: 0;
        text-align: center;
        font-size: 48pt;
        font-weight: bold;
        letter-spacing: 10pt;
        color: #D62828;
        opacity: 0.16;
    }
</style>
</head>
<body>
    <div class="flag flag-top"><span class="green"></span><span class="gold"></span><span class="red"></span><span class="ink"></span></div>
    <div class="frame"></div>

    @unless ($certificate->isValid())
        {{-- A reprint of something no longer good has to say so on its face. --}}
        <div class="void">{{ $certificate->hasExpired() ? 'EXPIRED' : 'NOT VALID' }}</div>
    @endunless

    <div class="sheet">
        @if ($logo)
            <img src="{{ $logo }}" alt="" class="logo">
        @endif

        <div class="federation">ZIMBABWE SWIMMING</div>
        <div class="standard">Certification and Grading Standards for Technical Officials</div>

        <div class="kind">CERTIFICATE OF ACCREDITATION</div>

        <div class="lede">This is to certify that</div>
        <div class="holder">{{ $certificate->holder_name }}</div>
        <div class="rule"></div>

        <div class="body">
            has completed the prescribed course of instruction, passed the written
            examination and satisfied the required poolside sessions, and is
            accredited by Zimbabwe Swimming as
        </div>

        <div class="award">{{ $certificate->title }}</div>

        @if ($certificate->grade)
            <div class="grade">at {{ $grade }} grade</div>
        @endif
    </div>

    <div class="foot">
        <table>
        <tr>
            <td class="sign">
                <div class="line"></div>
                <div class="who">{{ $signatory }}</div>
                <div class="role">{{ $signatoryRole }}</div>
            </td>

            <td class="dates">
                <span class="pair">
                    Issued<br><strong>{{ $certificate->issued_on->format('j F Y') }}</strong>
                </span>
                <span class="pair">
                    @if ($certificate->expires_on)
                        Valid until<br><strong>{{ $certificate->expires_on->format('j F Y') }}</strong>
                    @else
                        <br><strong>Does not expire</strong>
                    @endif
                </span>
            </td>

            <td class="verify">
                {!! $qr !!}
                <div class="code">{{ $certificate->verification_code }}</div>
                <div class="hint">Scan to confirm</div>
            </td>
        </tr>
        </table>
    </div>

    <div class="flag flag-bottom"><span class="ink"></span><span class="red"></span><span class="gold"></span><span class="green"></span></div>
</body>
</html>

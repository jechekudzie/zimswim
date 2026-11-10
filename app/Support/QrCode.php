<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode as Generator;
use chillerlan\QRCode\QROptions;

/**
 * The QR code printed on a certificate.
 *
 * Rendered as SVG and handed over as a base64 data URI, because the PDF is
 * built from HTML by DomPDF: it cannot fetch an image over the network, and
 * a certificate that needed one would be a certificate that stops printing
 * the day the host is unreachable.
 */
class QrCode
{
    /**
     * An <img> tag carrying the code, sized in whatever unit the template
     * uses.
     */
    public static function imageTag(string $data, string $size = '22mm'): string
    {
        return sprintf(
            '<img src="%s" style="width:%s;height:%s" alt="">',
            self::dataUri($data),
            $size,
            $size,
        );
    }

    public static function dataUri(string $data): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($data));
    }

    public static function svg(string $data): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            // Medium correction still scans off a creased printout, without
            // the density that makes a small code unreadable.
            'eccLevel' => EccLevel::M,
            'scale' => 5,
            'svgUseFillAttributes' => false,
            'drawLightModules' => false,
        ]);

        return (new Generator($options))->render($data);
    }
}

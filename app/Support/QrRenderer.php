<?php

namespace App\Support;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Renders check-in QR codes as inline SVG.
 *
 * SVG rather than PNG on purpose: PNG output needs ext-gd or ext-imagick, and
 * neither is guaranteed on a stock XAMPP install. SVG is pure PHP, scales to any
 * projector, and can be dropped straight into the page.
 */
class QrRenderer
{
    public function __construct(
        protected int $size = 340,
        protected int $margin = 2,
    ) {}

    public function svg(string $data): string
    {
        $qr = new QrCode(
            data: $data,
            encoding: new Encoding('UTF-8'),
            // High correction keeps the code readable from the back of a hall
            // and survives glare on a projector screen.
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $this->size,
            margin: $this->margin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $svg = (new SvgWriter)->write($qr)->getString();

        // Strip the XML prolog so the markup can be inlined inside an HTML body.
        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg) ?? $svg;
    }
}

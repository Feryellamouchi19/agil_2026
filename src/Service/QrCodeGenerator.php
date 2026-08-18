<?php

namespace App\Service;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeGenerator
{
    /**
     * Génère un QR Code SVG sous forme de Data URI (data:image/svg+xml;base64,...).
     * Ne nécessite PAS l'extension PHP GD.
     */
    public function generateDataUri(string $content): string
    {
        $writer = new SvgWriter();
        $qrCode = new QrCode(
            data: $content,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 10
        );

        $result = $writer->write($qrCode);

        return $result->getDataUri();
    }
}

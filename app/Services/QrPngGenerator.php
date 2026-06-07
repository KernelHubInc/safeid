<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Logo\Logo;

class QrPngGenerator
{
    public function make(string $url): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 400,
            margin: 10,
            logoPath: storage_path('app/public/emerion-logo2.png'),
            logoResizeToWidth: 120,
            logoPunchoutBackground: true
        );

        $result = $builder->build();

        header('Content-Type: '.$result->getMimeType());
        return $result->getString();
    }
}

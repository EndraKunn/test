<?php

namespace App\Modules\Admin\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final class QrCodeSvg
{
    /**
     * Generate a printable SVG QR code from the supplied URL.
     */
    public function render(string $url, int $size = 300): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd,
        );

        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }
}

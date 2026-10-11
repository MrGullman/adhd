<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    /**
     * An SVG QR code in the style guide's ink colour on white.
     */
    public static function svg(string $content, int $size = 240): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle($size, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(20, 34, 41))),
                new SvgImageBackEnd,
            ),
        ))->writeString($content);

        return trim(substr($svg, (int) strpos($svg, "\n") + 1));
    }
}

<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR codes rendered on our own server. The 2FA setup QR encodes the user's
 * TOTP secret, so it must never be sent to a third-party QR service.
 */
final class QrCode
{
    public static function svgDataUri(string $text, int $size = 220): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($size, 0), new SvgImageBackEnd())))->writeString($text);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}

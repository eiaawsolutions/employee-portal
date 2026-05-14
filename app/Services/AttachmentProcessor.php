<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Centralised secure-storage + image-compression for ticketing uploads.
 * Used by both ticket-creation attachments (TicketAttachment) and chat
 * message attachments (TicketMessageAttachment).
 *
 * Pipeline:
 *   1. Validate (caller-side via Laravel rules incl. valid_file_content).
 *   2. Generate a unique destination path under private storage.
 *   3. For images: GD-resize to max 1920px wide + re-encode (strips EXIF
 *      as a side effect).
 *   4. For PDFs/other: move the file as-is.
 *   5. Return metadata for the caller to insert into its own model.
 */
class AttachmentProcessor
{
    private const IMAGE_MAX_WIDTH = 1920;

    private const JPEG_QUALITY = 80;

    /**
     * Store an uploaded file in $directory under private storage and return
     * metadata suitable for TicketAttachment / TicketMessageAttachment.
     *
     * @return array{file_path:string, original_name:string, mime:string, size:int, is_image:bool}
     */
    public static function store(UploadedFile $file, string $directory, string $namePrefix = ''): array
    {
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $originalName = $file->getClientOriginalName();
        $isImage = str_starts_with($mime, 'image/');
        $ext = $file->getClientOriginalExtension() ?: 'bin';

        $relativePath = trim($directory, '/').'/'
            .$namePrefix.time().'_'.Str::random(10).'.'.$ext;
        $absolutePath = Storage::disk('local')->path($relativePath);

        if (! is_dir(dirname($absolutePath))) {
            @mkdir(dirname($absolutePath), 0755, true);
        }

        $compressed = false;
        if ($isImage) {
            $compressed = self::compressImage(
                $file->getRealPath(),
                $absolutePath,
                self::IMAGE_MAX_WIDTH,
                self::JPEG_QUALITY
            );
        }

        if (! $compressed) {
            // Non-image OR image compression failed → move the upload as-is.
            $file->move(dirname($absolutePath), basename($absolutePath));
        }

        $finalSize = @filesize($absolutePath) ?: $file->getSize();

        return [
            'file_path' => $relativePath,
            'original_name' => $originalName,
            'mime' => $mime,
            'size' => $finalSize,
            'is_image' => $isImage,
        ];
    }

    /**
     * Resize + re-encode an image with GD. Strips EXIF as a side effect of
     * re-encoding. Preserves transparency for PNG/GIF/WEBP. Returns false
     * if GD can't handle the image type — caller falls back to move-as-is.
     */
    private static function compressImage(string $sourcePath, string $destPath, int $maxWidth, int $jpegQuality): bool
    {
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return false;
        }
        [$width, $height, $type] = $info;

        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => @imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            default => null,
        };
        if (! $source) {
            return false;
        }

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round($height * ($maxWidth / $width));
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        $dest = imagecreatetruecolor($newWidth, $newHeight);

        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
            $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
            imagefilledrectangle($dest, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = match ($type) {
            IMAGETYPE_JPEG => @imagejpeg($dest, $destPath, $jpegQuality),
            IMAGETYPE_PNG => @imagepng($dest, $destPath, 6),
            IMAGETYPE_GIF => @imagegif($dest, $destPath),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? @imagewebp($dest, $destPath, $jpegQuality) : false,
            default => false,
        };

        imagedestroy($source);
        imagedestroy($dest);

        return $saved !== false;
    }
}

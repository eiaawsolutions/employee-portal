<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RuntimeException;

/**
 * A real path on this machine for a file kept on a Storage disk.
 *
 * Code that must hand a path to something outside Flysystem (GD, Ghostscript,
 * mime_content_type, an AI upload) calls this instead of ->path(), which only
 * exists on local disks. On a local disk it returns the file's own path; on R2
 * it downloads a temporary copy that is removed when the process ends.
 */
final class LocalFile
{
    public static function for(string $disk, string $path): string
    {
        /** @var FilesystemAdapter $fs */
        $fs = Storage::disk($disk);

        if ($fs->getAdapter() instanceof LocalFilesystemAdapter) {
            return $fs->path($path);
        }

        $stream = $fs->readStream($path);
        if ($stream === null || $stream === false) {
            throw new RuntimeException("File not found on disk [{$disk}]: {$path}");
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $tmp = tempnam(sys_get_temp_dir(), 'wf_').($ext !== '' ? '.'.$ext : '');
        $out = fopen($tmp, 'wb');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);

        register_shutdown_function(static fn () => @unlink($tmp));

        return $tmp;
    }
}

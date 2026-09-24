<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves files from the 'public' disk at /storage/{path}.
 *
 * With local storage the web server finds these through the public/storage
 * symlink and never reaches Laravel. With R2_ENABLED the files live in the
 * private R2 bucket, so the missing static file falls through to this route,
 * which streams it. Views and emails keep using asset('storage/...') unchanged.
 * Only the 'public' disk is reachable here; private HR files go through
 * SecureFileController with role checks.
 */
class PublicStorageController extends Controller
{
    public function show(string $path): Response
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

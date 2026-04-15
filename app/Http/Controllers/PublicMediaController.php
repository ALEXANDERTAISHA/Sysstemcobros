<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicMediaController extends Controller
{
    public function show(string $path): StreamedResponse
    {
        $normalizedPath = ltrim($path, '/');

        abort_if($normalizedPath === '' || str_contains($normalizedPath, '..'), 404);
        abort_unless(Storage::disk('public')->exists($normalizedPath), 404);

        $absolutePath = Storage::disk('public')->path($normalizedPath);
        $mimeType = @mime_content_type($absolutePath) ?: 'application/octet-stream';

        return response()->stream(function () use ($absolutePath) {
            $stream = fopen($absolutePath, 'rb');

            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
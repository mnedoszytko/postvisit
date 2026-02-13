<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Storage;

trait ResolvesAudioPaths
{
    /**
     * Resolve a storage path to an absolute local path for CLI tools (Whisper).
     * For non-local disks, downloads the file to a temp location.
     */
    protected function resolveLocalPath(string $disk, string $storagePath): string
    {
        if ($disk === 'local') {
            return Storage::disk($disk)->path($storagePath);
        }

        $ext = pathinfo($storagePath, PATHINFO_EXTENSION);
        $tmp = tempnam(sys_get_temp_dir(), 'pv_audio_').'.'.$ext;
        file_put_contents($tmp, Storage::disk($disk)->get($storagePath));

        return $tmp;
    }

    /**
     * Clean up temp file created by resolveLocalPath for non-local disks.
     */
    protected function cleanupTempFile(string $disk, string $path): void
    {
        if ($disk !== 'local' && file_exists($path)) {
            @unlink($path);
        }
    }
}

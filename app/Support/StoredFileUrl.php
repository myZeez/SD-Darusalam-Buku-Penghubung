<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

class StoredFileUrl
{
    /**
     * Build a browser-safe URL for a file that may live on Filament's private disk.
     */
    public static function for(?string $path, ?string $diskName = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL) !== false || str_starts_with($path, 'data:')) {
            return $path;
        }

        $diskName ??= config('filament.default_filesystem_disk', config('filesystems.default'));
        $storage = Storage::disk($diskName);

        try {
            if (! $storage->exists($path)) {
                return null;
            }
        } catch (Throwable) {
            // Some remote disks cannot check file existence efficiently. Continue with the URL.
        }

        if ($diskName !== 'public') {
            try {
                return $storage->temporaryUrl(
                    $path,
                    now()->addMinutes(config('filament.temporary_file_url_expiry_minutes', 30))->endOfHour(),
                );
            } catch (Throwable) {
                // Fall back to the disk URL when temporary links are not supported.
            }
        }

        return $storage->url($path);
    }
}

<?php

namespace DirectoryTree\PrivacyFilter\Support;

use RuntimeException;
use ZipArchive;

/**
 * Extract zip archives using PHP's ZipArchive extension.
 */
class Zip
{
    /**
     * Extract the given zip archive into the destination directory.
     */
    public static function extract(string $archive, string $destination): void
    {
        $zip = new ZipArchive;

        if ($zip->open($archive) !== true) {
            throw new RuntimeException("Unable to open zip archive [{$archive}].");
        }

        if (! $zip->extractTo($destination)) {
            $zip->close();

            throw new RuntimeException("Unable to extract zip archive [{$archive}].");
        }

        $zip->close();
    }
}

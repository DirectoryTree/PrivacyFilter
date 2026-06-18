<?php

namespace DirectoryTree\PrivacyFilter\Support;

use FilesystemIterator;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Copy directories while preserving filesystem metadata required by binaries.
 */
class Directory
{
    /**
     * Copy the given directory into the destination path.
     */
    public static function copy(string $source, string $destination): void
    {
        if (! File::copyDirectory($source, $destination)) {
            throw new RuntimeException("Unable to copy directory [{$source}] to [{$destination}].");
        }

        static::copySymlinks($source, $destination);
    }

    /**
     * Restore symlinks after copying the source directory.
     */
    protected static function copySymlinks(string $source, string $destination): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            if (! $file->isLink()) {
                continue;
            }

            $target = $destination.DIRECTORY_SEPARATOR.$iterator->getSubPathName();

            if (File::exists($target) || is_link($target)) {
                File::delete($target);
            }

            if (! symlink(readlink($file->getPathname()), $target)) {
                throw new RuntimeException("Unable to create symlink [{$target}].");
            }
        }
    }
}

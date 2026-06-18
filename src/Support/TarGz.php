<?php

namespace DirectoryTree\PrivacyFilter\Support;

use Illuminate\Support\Facades\File;
use PharData;
use Symfony\Component\Process\Process;

/**
 * Extract gzipped tar archives.
 */
class TarGz
{
    /**
     * Extract the given gzipped tar archive into the destination directory.
     */
    public static function extract(string $archive, string $destination): void
    {
        if (PHP_OS_FAMILY !== 'Windows' && static::extractWithTar($archive, $destination)) {
            return;
        }

        $tar = substr($archive, 0, -3);

        if (! File::exists($tar)) {
            (new PharData($archive))->decompress();
        }

        Tar::extract($tar, $destination);
    }

    /**
     * Extract the archive using the system tar command.
     */
    protected static function extractWithTar(string $archive, string $destination): bool
    {
        $process = new Process(['tar', '-xzf', $archive, '-C', $destination]);

        $process->run();

        return $process->isSuccessful();
    }
}

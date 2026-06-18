<?php

namespace DirectoryTree\PrivacyFilter\Support;

use Exception;
use PharData;
use RuntimeException;

/**
 * Extract tar archives using PHP's PharData support.
 */
class Tar
{
    /**
     * Extract the given tar archive into the destination directory.
     */
    public static function extract(string $archive, string $destination): void
    {
        try {
            (new PharData($archive))->extractTo($destination, null, true);
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to extract tar archive [{$archive}].", 0, $exception);
        }
    }
}

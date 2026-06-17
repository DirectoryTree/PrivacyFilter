<?php

namespace DirectoryTree\PrivacyFilter\Exceptions;

use RuntimeException;

/**
 * Exception thrown when the privacy-filter process fails.
 */
class PrivacyFilterFailedException extends RuntimeException
{
    /**
     * Create a new exception from process output.
     */
    public static function because(string $error, int $exitCode): self
    {
        return new self(trim($error) ?: 'The privacy-filter process failed without output.', $exitCode);
    }
}

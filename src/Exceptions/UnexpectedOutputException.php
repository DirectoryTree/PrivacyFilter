<?php

namespace DirectoryTree\PrivacyFilter\Exceptions;

use RuntimeException;

/**
 * Exception thrown when privacy-filter returns output the package cannot parse.
 */
class UnexpectedOutputException extends RuntimeException
{
    /**
     * Create a new exception for invalid process output.
     */
    public static function fromOutput(string $output): self
    {
        return new self('The privacy-filter process returned invalid output: '.trim($output));
    }
}

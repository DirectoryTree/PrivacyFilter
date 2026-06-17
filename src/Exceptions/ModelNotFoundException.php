<?php

namespace DirectoryTree\PrivacyFilter\Exceptions;

use RuntimeException;

/**
 * Exception thrown when the configured GGUF model cannot be found.
 */
class ModelNotFoundException extends RuntimeException
{
    /**
     * Create a new exception for the missing model path.
     */
    public static function at(string $path): self
    {
        return new self("The privacy-filter model does not exist at [{$path}].");
    }
}

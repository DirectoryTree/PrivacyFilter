<?php

namespace DirectoryTree\PrivacyFilter\Facades;

use DirectoryTree\PrivacyFilter\Classifier;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int, \DirectoryTree\PrivacyFilter\Entity> entities(string $text, ?float $threshold = null)
 *
 * @see \DirectoryTree\PrivacyFilter\Classifier
 */
class PrivacyFilter extends Facade
{
    /**
     * Get the registered component name.
     */
    protected static function getFacadeAccessor(): string
    {
        return Classifier::class;
    }
}

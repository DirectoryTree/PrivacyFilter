<?php

namespace DirectoryTree\PrivacyFilter\Facades;

use DirectoryTree\PrivacyFilterClassifier\Classifier;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int, \DirectoryTree\PrivacyFilterClassifier\Entity> entities(string $text, ?float $threshold = null)
 *
 * @see \DirectoryTree\PrivacyFilterClassifier\Classifier
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

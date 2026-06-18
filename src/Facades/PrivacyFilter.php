<?php

namespace DirectoryTree\PrivacyFilter\Facades;

use DirectoryTree\PrivacyFilter\Testing\PrivacyFilterFake;
use DirectoryTree\PrivacyFilterClassifier\ClassifierInterface;
use DirectoryTree\PrivacyFilterClassifier\Entity;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int, Entity> entities(string $text, ?float $threshold = null)
 *
 * @see ClassifierInterface
 * @see PrivacyFilterFake
 */
class PrivacyFilter extends Facade
{
    /**
     * Replace the bound privacy-filter classifier with a fake.
     *
     * @param  array<string, array<int, Entity>>|array<int, Entity>  $entities
     */
    public static function fake(array $entities = []): PrivacyFilterFake
    {
        static::swap($fake = new PrivacyFilterFake($entities));

        return $fake;
    }

    /**
     * Get the registered component name.
     */
    protected static function getFacadeAccessor(): string
    {
        return ClassifierInterface::class;
    }
}

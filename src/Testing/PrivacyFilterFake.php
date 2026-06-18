<?php

namespace DirectoryTree\PrivacyFilter\Testing;

use DirectoryTree\PrivacyFilterClassifier\Classifier;
use DirectoryTree\PrivacyFilterClassifier\ClassifierInterface;
use DirectoryTree\PrivacyFilterClassifier\Entity;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Fake privacy-filter classifier implementation for application tests.
 */
class PrivacyFilterFake implements ClassifierInterface
{
    /**
     * Create a new privacy-filter fake instance.
     *
     * @param  array<string, array<int, Entity>>|array<int, Entity>  $entities
     */
    public function __construct(
        protected array $entities = [],
    ) {}

    /**
     * Get the entities detected in the given text.
     *
     * @return array<int, Entity>
     */
    public function entities(string $text, ?float $threshold = null): array
    {
        if (Arr::isList($this->entities)) {
            return $this->entities;
        }

        foreach ($this->entities as $pattern => $entities) {
            if (Str::is($pattern, $text)) {
                return $entities;
            }
        }

        return [];
    }
}

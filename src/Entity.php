<?php

namespace DirectoryTree\PrivacyFilter;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * A classified entity detected by privacy-filter.
 *
 * @implements Arrayable<string, mixed>
 */
readonly class Entity implements Arrayable, JsonSerializable
{
    /**
     * Create a new entity instance.
     */
    public function __construct(
        public string $type,
        public int $start,
        public int $end,
        public float $score,
        public string $text,
    ) {}

    /**
     * Get the entity length in bytes.
     */
    public function length(): int
    {
        return $this->end - $this->start;
    }

    /**
     * Convert the entity to an array.
     *
     * @return array{type: string, start: int, end: int, score: float, text: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'start' => $this->start,
            'end' => $this->end,
            'score' => $this->score,
            'text' => $this->text,
        ];
    }

    /**
     * Convert the entity to a JSON-serializable value.
     *
     * @return array{type: string, start: int, end: int, score: float, text: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

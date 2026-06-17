<?php

namespace DirectoryTree\PrivacyFilter;

use DirectoryTree\PrivacyFilter\Exceptions\BinaryNotFoundException;
use DirectoryTree\PrivacyFilter\Exceptions\ModelNotFoundException;
use DirectoryTree\PrivacyFilter\Exceptions\PrivacyFilterFailedException;
use DirectoryTree\PrivacyFilter\Exceptions\UnexpectedOutputException;
use Symfony\Component\Process\Process;

/**
 * Runtime API for classifying text with the local privacy-filter binary.
 */
class Classifier
{
    /**
     * Create a new privacy-filter API instance.
     */
    public function __construct(
        protected string $binaryPath,
        protected string $modelPath,
        protected float $threshold,
        protected float $timeout,
    ) {}

    /**
     * Get the entities detected in the given text.
     *
     * @return array<int, Entity>
     */
    public function entities(string $text, ?float $threshold = null): array
    {
        $this->ensureBinaryExists();
        $this->ensureModelExists();

        $process = new Process([
            $this->binaryPath,
            '--classify',
            $this->modelPath,
            (string) ($threshold ?? $this->threshold),
        ]);

        $process->setInput($text);
        $process->setTimeout($this->timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            throw PrivacyFilterFailedException::because(
                error: $process->getErrorOutput(),
                exitCode: $process->getExitCode() ?? 1,
            );
        }

        return $this->parseEntities($process->getOutput(), $text);
    }

    /**
     * Parse privacy-filter CLI output into entity instances.
     *
     * @return array<int, Entity>
     */
    protected function parseEntities(string $output, string $sourceText): array
    {
        $decoded = json_decode($output, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->entitiesFromDecodedOutput($decoded, $sourceText);
        }

        return $this->entitiesFromOutputPrefixes($output, $sourceText);
    }

    /**
     * Convert decoded JSON output into entity instances.
     *
     * @param  array<int, array<string, mixed>>  $entities
     * @return array<int, Entity>
     */
    protected function entitiesFromDecodedOutput(array $entities, string $sourceText): array
    {
        return array_map(
            fn (array $entity): Entity => $this->entityFromArray($entity, $sourceText),
            $entities,
        );
    }

    /**
     * Parse entity prefixes from invalid JSON output.
     *
     * The upstream CLI includes raw entity text in the JSON response without
     * escaping it first. This fallback uses the byte offsets and ignores the
     * echoed text field so quoted entity text can still be handled safely.
     *
     * @return array<int, Entity>
     */
    protected function entitiesFromOutputPrefixes(string $output, string $sourceText): array
    {
        preg_match_all(
            '/\{\s*"entity_group"\s*:\s*"(?P<type>[^"]+)"\s*,\s*"start"\s*:\s*(?P<start>-?\d+)\s*,\s*"end"\s*:\s*(?P<end>-?\d+)\s*,\s*"score"\s*:\s*(?P<score>-?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?)\s*,\s*"text"\s*:\s*"/',
            $output,
            $matches,
            PREG_SET_ORDER,
        );

        if ($matches === []) {
            throw UnexpectedOutputException::fromOutput($output);
        }

        return array_map(
            fn (array $entity): Entity => $this->entityFromArray($entity, $sourceText),
            $matches,
        );
    }

    /**
     * Create an entity from a decoded entity payload.
     *
     * @param  array<string, mixed>  $entity
     */
    protected function entityFromArray(array $entity, string $sourceText): Entity
    {
        $start = (int) ($entity['start'] ?? -1);
        $end = (int) ($entity['end'] ?? -1);
        $score = (float) ($entity['score'] ?? 0);
        $type = (string) ($entity['entity_group'] ?? $entity['type'] ?? $entity['label'] ?? '');

        if ($type === '' || $start < 0 || $end < $start || $end > strlen($sourceText)) {
            throw UnexpectedOutputException::fromOutput(json_encode($entity, JSON_THROW_ON_ERROR));
        }

        return new Entity(
            type: $type,
            start: $start,
            end: $end,
            score: $score,
            text: substr($sourceText, $start, $end - $start),
        );
    }

    /**
     * Ensure the configured binary exists.
     */
    protected function ensureBinaryExists(): void
    {
        if (! is_file($this->binaryPath)) {
            throw BinaryNotFoundException::at($this->binaryPath);
        }
    }

    /**
     * Ensure the configured GGUF model exists.
     */
    protected function ensureModelExists(): void
    {
        if (! is_file($this->modelPath)) {
            throw ModelNotFoundException::at($this->modelPath);
        }
    }
}

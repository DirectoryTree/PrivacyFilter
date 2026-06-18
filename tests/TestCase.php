<?php

namespace DirectoryTree\PrivacyFilter\Tests;

use DirectoryTree\PrivacyFilter\PrivacyFilterServiceProvider;
use DirectoryTree\PrivacyFilterClassifier\Classifier;
use Orchestra\Testbench\TestCase as Orchestra;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Base test case for package feature tests.
 */
abstract class TestCase extends Orchestra
{
    /**
     * The fake privacy-filter binary path used by tests.
     */
    protected string $fakePrivacyFilterBinaryPath;

    /**
     * The fake GGUF model path used by tests.
     */
    protected string $fakePrivacyFilterModelPath;

    /**
     * Temporary paths that should be deleted after a test.
     *
     * @var array<int, string>
     */
    protected array $temporaryPaths = [];

    /**
     * Get package service providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PrivacyFilterServiceProvider::class,
        ];
    }

    /**
     * Configure the application to use the test privacy-filter binary.
     */
    protected function useFakePrivacyFilter(?string $modelPath = null): void
    {
        $this->clearFakePrivacyFilterEnvironment();

        $this->fakePrivacyFilterBinaryPath = realpath(__DIR__.'/Fixtures/privacy-filter') ?: __DIR__.'/Fixtures/privacy-filter';
        $this->fakePrivacyFilterModelPath = $modelPath ?: sys_get_temp_dir().'/privacy-filter-test-model-'.spl_object_id($this).'.gguf';

        chmod($this->fakePrivacyFilterBinaryPath, 0755);
        file_put_contents($this->fakePrivacyFilterModelPath, 'model');

        config()->set('privacy-filter.paths.binary', $this->fakePrivacyFilterBinaryPath);
        config()->set('privacy-filter.paths.model', $this->fakePrivacyFilterModelPath);
        config()->set('privacy-filter.process.timeout', 5);

        $this->app->forgetInstance(Classifier::class);
    }

    /**
     * Set environment variables consumed by the fake privacy-filter binary.
     *
     * @param  array<string, string>  $environment
     */
    protected function setFakePrivacyFilterEnvironment(array $environment): void
    {
        foreach ($environment as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Clear environment variables consumed by the fake privacy-filter binary.
     */
    protected function clearFakePrivacyFilterEnvironment(): void
    {
        foreach ([
            'PRIVACY_FILTER_FAKE_MODE',
            'PRIVACY_FILTER_FAKE_NEEDLE',
            'PRIVACY_FILTER_FAKE_TYPE',
        ] as $key) {
            putenv($key);

            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    /**
     * Create a temporary directory for the current test.
     */
    protected function makeTemporaryDirectory(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'privacy-filter-test-'.bin2hex(random_bytes(8));

        mkdir($path, 0755, true);

        $this->temporaryPaths[] = $path;

        return $path;
    }

    /**
     * Create a local privacy-filter binary archive for installer tests.
     */
    protected function makePrivacyFilterArchive(): string
    {
        $directory = $this->makeTemporaryDirectory();
        $root = $directory.DIRECTORY_SEPARATOR.'privacy-filter-test';
        $bin = $root.DIRECTORY_SEPARATOR.'bin';
        $lib = $root.DIRECTORY_SEPARATOR.'lib';
        $binary = $bin.DIRECTORY_SEPARATOR.'privacy-filter';
        $archive = $directory.DIRECTORY_SEPARATOR.'privacy-filter-test.tar.gz';

        mkdir($bin, 0755, true);
        mkdir($lib, 0755, true);
        file_put_contents($binary, "#!/usr/bin/env sh\nprintf 'privacy-filter test binary'\n");
        file_put_contents($lib.DIRECTORY_SEPARATOR.'libggml.0.15.1.dylib', 'privacy-filter test library');
        symlink('libggml.0.15.1.dylib', $lib.DIRECTORY_SEPARATOR.'libggml.0.dylib');
        symlink('libggml.0.dylib', $lib.DIRECTORY_SEPARATOR.'libggml.dylib');
        chmod($binary, 0755);

        $process = new Process(['tar', '-czf', $archive, '-C', $directory, 'privacy-filter-test']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Unable to create privacy-filter test archive.');
        }

        return $archive;
    }

    /**
     * Create a local GGUF model fixture for installer tests.
     */
    protected function makePrivacyFilterModel(): string
    {
        $directory = $this->makeTemporaryDirectory();
        $path = $directory.DIRECTORY_SEPARATOR.'privacy-filter.gguf';

        file_put_contents($path, 'privacy-filter test model');

        return $path;
    }

    /**
     * Delete a temporary file or directory.
     */
    protected function deleteTemporaryPath(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);

            return;
        }

        if (! is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($path);
    }

    /**
     * Clean up test resources.
     */
    protected function tearDown(): void
    {
        try {
            $this->clearFakePrivacyFilterEnvironment();

            if (isset($this->fakePrivacyFilterModelPath)) {
                @unlink($this->fakePrivacyFilterModelPath);
            }

            foreach (array_reverse($this->temporaryPaths) as $path) {
                $this->deleteTemporaryPath($path);
            }
        } finally {
            parent::tearDown();
        }
    }
}

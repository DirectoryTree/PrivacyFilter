<?php

namespace DirectoryTree\PrivacyFilter\Tests;

use DirectoryTree\PrivacyFilter\Classifier;
use DirectoryTree\PrivacyFilter\PrivacyFilterServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

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
        config()->set('privacy-filter.model.threshold', 0.5);
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
     * Clean up test resources.
     */
    protected function tearDown(): void
    {
        try {
            $this->clearFakePrivacyFilterEnvironment();

            if (isset($this->fakePrivacyFilterModelPath)) {
                @unlink($this->fakePrivacyFilterModelPath);
            }
        } finally {
            parent::tearDown();
        }
    }
}

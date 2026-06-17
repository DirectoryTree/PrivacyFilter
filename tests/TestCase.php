<?php

namespace DirectoryTree\PrivacyFilter\Tests;

use DirectoryTree\PrivacyFilter\PrivacyFilterServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base test case for package feature tests.
 */
abstract class TestCase extends Orchestra
{
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
}

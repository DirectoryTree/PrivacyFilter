<?php

use DirectoryTree\PrivacyFilter\Classifier;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\artisan;

it('merges the package configuration into the testbench application', function () {
    expect(config('privacy-filter.release.repository'))->toBe('DirectoryTree/PrivacyFilterBinaries')
        ->and(config('privacy-filter.release.version'))->toBe('v1.0.0')
        ->and(config('privacy-filter.paths.binary'))->toBe(storage_path('app/privacy-filter/bin/privacy-filter'))
        ->and(config('privacy-filter.paths.model'))->toBe(storage_path('app/privacy-filter/models/privacy-filter-f16.gguf'))
        ->and(config('privacy-filter.model.threshold'))->toBe(0.5)
        ->and(config('privacy-filter.process.timeout'))->toBe(60.0);
});

it('registers the classifier as a singleton', function () {
    expect(app(Classifier::class))->toBeInstanceOf(Classifier::class)
        ->and(app(Classifier::class))->toBe(app(Classifier::class));
});

it('registers the package artisan commands', function () {
    expect(array_keys(Artisan::all()))->toContain(
        'privacy-filter:install',
        'privacy-filter:install-binary',
        'privacy-filter:install-model',
    );
});

it('publishes the package configuration file', function () {
    $path = config_path('privacy-filter.php');
    $original = is_file($path) ? file_get_contents($path) : null;

    @unlink($path);

    try {
        artisan('vendor:publish', [
            '--tag' => 'privacy-filter-config',
            '--force' => true,
        ])->assertSuccessful();

        expect($path)->toBeFile();

        $config = require $path;

        expect($config)->toHaveKeys([
            'release',
            'paths',
            'model',
            'process',
        ]);
    } finally {
        if ($original !== null) {
            file_put_contents($path, $original);
        } else {
            @unlink($path);
        }
    }
});

<?php

use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;

it('runs the package installer', function () {
    $archive = $this->makePrivacyFilterArchive();
    $model = $this->makePrivacyFilterModel();
    $binaryTarget = $this->makeTemporaryDirectory().DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'privacy-filter';
    $modelTarget = $this->makeTemporaryDirectory().DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'privacy-filter.gguf';
    $binaryUrl = 'https://example.com/privacy-filter.tar.gz';
    $modelUrl = 'https://example.com/privacy-filter.gguf';

    Http::fake([
        $binaryUrl => Http::response(file_get_contents($archive)),
        $modelUrl => Http::response(file_get_contents($model)),
    ]);

    config()->set('privacy-filter.paths.binary', $binaryTarget);
    config()->set('privacy-filter.paths.model', $modelTarget);

    artisan('privacy-filter:install', [
        '--binary-url' => $binaryUrl,
        '--model-url' => $modelUrl,
    ])
        ->expectsOutputToContain('Installing privacy-filter binary.')
        ->expectsOutputToContain('Installing privacy-filter model.')
        ->assertSuccessful();

    expect($binaryTarget)
        ->toBeFile()
        ->and($modelTarget)->toBeFile();
});

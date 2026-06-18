<?php

use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;

it('installs the configured model', function () {
    $model = $this->makePrivacyFilterModel();
    $target = $this->makeTemporaryDirectory().DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'privacy-filter.gguf';
    $url = 'https://example.com/privacy-filter.gguf';

    Http::fake([
        $url => Http::response(file_get_contents($model)),
    ]);

    config()->set('privacy-filter.paths.model', $target);

    artisan('privacy-filter:install-model', [
        '--url' => $url,
    ])
        ->expectsOutputToContain('Installing privacy-filter model.')
        ->expectsOutputToContain('Downloading model')
        ->expectsOutputToContain($target)
        ->assertSuccessful();

    expect($target)
        ->toBeFile()
        ->and(file_get_contents($target))->toBe('privacy-filter test model');
});

it('does not overwrite an existing model unless forced', function () {
    $model = $this->makePrivacyFilterModel();
    $target = $this->makeTemporaryDirectory().DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'privacy-filter.gguf';
    $url = 'https://example.com/privacy-filter.gguf';

    Http::fake([
        $url => Http::response(file_get_contents($model)),
    ]);

    mkdir(dirname($target), 0755, true);
    file_put_contents($target, 'existing model');

    config()->set('privacy-filter.paths.model', $target);

    artisan('privacy-filter:install-model', [
        '--url' => $url,
    ])
        ->expectsOutputToContain('The privacy-filter model already exists.')
        ->assertSuccessful();

    expect(file_get_contents($target))->toBe('existing model');

    artisan('privacy-filter:install-model', [
        '--url' => $url,
        '--force' => true,
    ])->assertSuccessful();

    expect(file_get_contents($target))->toBe('privacy-filter test model');
});

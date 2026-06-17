<?php

use function Pest\Laravel\artisan;

it('prepares the model installer', function () {
    config()->set('privacy-filter.paths.model', '/tmp/privacy-filter/models/privacy-filter.gguf');

    artisan('privacy-filter:install-model', [
        '--url' => 'https://example.com/privacy-filter.gguf',
    ])
        ->expectsOutputToContain('Preparing privacy-filter model installation.')
        ->expectsOutputToContain('https://example.com/privacy-filter.gguf')
        ->expectsOutputToContain('/tmp/privacy-filter/models/privacy-filter.gguf')
        ->assertSuccessful();
});

<?php

use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;

it('installs the binary for the current platform', function () {
    $archive = $this->makePrivacyFilterArchive();
    $target = $this->makeTemporaryDirectory().DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'privacy-filter';
    $url = 'https://example.com/privacy-filter.tar.gz';

    Http::fake([
        $url => Http::response(file_get_contents($archive)),
    ]);

    config()->set('privacy-filter.paths.binary', $target);

    artisan('privacy-filter:install-binary', [
        '--release' => 'v9.9.9',
        '--url' => $url,
    ])
        ->expectsOutputToContain('Installing privacy-filter binary.')
        ->expectsOutputToContain('Downloading binary archive')
        ->expectsOutputToContain($target)
        ->assertSuccessful();

    expect($target)
        ->toBeFile()
        ->and(file_get_contents($target))->toContain('privacy-filter test binary')
        ->and(substr(sprintf('%o', fileperms($target)), -3))->toBe('755');
});

it('does not overwrite an existing binary unless forced', function () {
    $archive = $this->makePrivacyFilterArchive();
    $target = $this->makeTemporaryDirectory().DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'privacy-filter';
    $url = 'https://example.com/privacy-filter.tar.gz';

    Http::fake([
        $url => Http::response(file_get_contents($archive)),
    ]);

    mkdir(dirname($target), 0755, true);
    file_put_contents($target, 'existing binary');

    config()->set('privacy-filter.paths.binary', $target);

    artisan('privacy-filter:install-binary', [
        '--url' => $url,
    ])
        ->expectsOutputToContain('The privacy-filter binary already exists.')
        ->assertSuccessful();

    expect(file_get_contents($target))->toBe('existing binary');

    artisan('privacy-filter:install-binary', [
        '--url' => $url,
        '--force' => true,
    ])->assertSuccessful();

    expect(file_get_contents($target))->toContain('privacy-filter test binary');
});

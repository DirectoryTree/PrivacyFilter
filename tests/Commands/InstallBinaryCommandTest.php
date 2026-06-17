<?php

use function Pest\Laravel\artisan;

it('prepares the binary installer', function () {
    config()->set('privacy-filter.release.repository', 'DirectoryTree/FakePrivacyFilterBinaries');
    config()->set('privacy-filter.paths.binary', '/tmp/privacy-filter/bin/privacy-filter');

    artisan('privacy-filter:install-binary', [
        '--release' => 'v9.9.9',
    ])
        ->expectsOutputToContain('Preparing privacy-filter binary installation.')
        ->expectsOutputToContain('DirectoryTree/FakePrivacyFilterBinaries')
        ->expectsOutputToContain('v9.9.9')
        ->expectsOutputToContain('/tmp/privacy-filter/bin/privacy-filter')
        ->assertSuccessful();
});

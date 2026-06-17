<?php

use function Pest\Laravel\artisan;

it('runs the package installer', function () {
    artisan('privacy-filter:install')
        ->expectsOutputToContain('Preparing privacy-filter binary installation.')
        ->expectsOutputToContain('Preparing privacy-filter model installation.')
        ->assertSuccessful();
});

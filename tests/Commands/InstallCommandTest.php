<?php

it('runs the package installer', function () {
    $this->artisan('privacy-filter:install')
        ->assertSuccessful();
});

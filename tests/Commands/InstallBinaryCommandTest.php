<?php

it('prepares the binary installer', function () {
    $this->artisan('privacy-filter:install-binary')
        ->assertSuccessful();
});

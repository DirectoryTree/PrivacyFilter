<?php

it('prepares the model installer', function () {
    $this->artisan('privacy-filter:install-model')
        ->assertSuccessful();
});

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Binary Release
    |--------------------------------------------------------------------------
    |
    | The Laravel package installs compiled privacy-filter.cpp binaries from
    | the companion GitHub releases repository.
    |
    */

    'release' => [
        'repository' => env('PRIVACY_FILTER_BINARY_REPOSITORY', 'DirectoryTree/PrivacyFilterBinaries'),
        'version' => env('PRIVACY_FILTER_BINARY_VERSION', 'v1.0.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | These paths are used by the Artisan installers and, later, by the runtime
    | PHP wrapper when invoking the local privacy-filter executable.
    |
    */

    'paths' => [
        'binary' => env('PRIVACY_FILTER_BINARY_PATH', storage_path('app/privacy-filter/bin/privacy-filter')),
        'model' => env('PRIVACY_FILTER_MODEL_PATH', storage_path('app/privacy-filter/models/privacy-filter-f16.gguf')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    |
    | LocalAI currently publishes one English GGUF and one multilingual GGUF for
    | privacy-filter.cpp. The English F16 model is the smallest compatible file
    | available for basic application use and CI smoke tests.
    |
    */

    'model' => [
        'url' => env(
            'PRIVACY_FILTER_MODEL_URL',
            'https://huggingface.co/LocalAI-io/privacy-filter-GGUF/resolve/main/privacy-filter-f16.gguf',
        ),

        'threshold' => (float) env('PRIVACY_FILTER_THRESHOLD', 0.5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Process
    |--------------------------------------------------------------------------
    |
    | The PHP API shells out to the local privacy-filter binary. This timeout
    | protects requests and jobs from hanging indefinitely if the process stalls.
    |
    */

    'process' => [
        'timeout' => (float) env('PRIVACY_FILTER_TIMEOUT', 60),
    ],
];

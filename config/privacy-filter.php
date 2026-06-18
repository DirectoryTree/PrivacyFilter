<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Privacy Filter Paths
    |--------------------------------------------------------------------------
    |
    | Here you may configure the paths where the privacy-filter binary and
    | model should be installed. These paths will be used by the installer
    | commands and by the runtime classifier.
    |
    */

    'paths' => [
        'binary' => env('PRIVACY_FILTER_BINARY_PATH', storage_path('app/privacy-filter/bin/privacy-filter')),
        'model' => env('PRIVACY_FILTER_MODEL_PATH', storage_path('app/privacy-filter/models/privacy-filter-f16.gguf')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Process Timeout
    |--------------------------------------------------------------------------
    |
    | The classifier runs the privacy-filter binary as a separate process. This
    | value determines how many seconds the process may run before timing out.
    |
    */

    'process' => [
        'timeout' => (float) env('PRIVACY_FILTER_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy Filter Model
    |--------------------------------------------------------------------------
    |
    | This URL points to the GGUF model that should be downloaded by the model
    | installer. You may change this value if you would like to use another
    | compatible privacy-filter model.
    |
    */

    'model' => [
        'url' => env(
            'PRIVACY_FILTER_MODEL_URL',
            'https://huggingface.co/LocalAI-io/privacy-filter-GGUF/resolve/main/privacy-filter-f16.gguf',
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Binary Release Source
    |--------------------------------------------------------------------------
    |
    | The binary installer downloads compiled privacy-filter binaries from a
    | GitHub release. You may customize the repository or release version
    | used when installing the binary.
    |
    */

    'release' => [
        'repository' => env('PRIVACY_FILTER_BINARY_REPOSITORY', 'DirectoryTree/PrivacyFilterBinaries'),
        'version' => env('PRIVACY_FILTER_BINARY_VERSION', 'v1.0.0'),
    ],
];

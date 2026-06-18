<div align="center">
<h1>Privacy Filter</h1>
<p>
<a href="https://github.com/DirectoryTree/PrivacyFilter/actions/workflows/run-tests.yml"><img src="https://github.com/DirectoryTree/PrivacyFilter/actions/workflows/run-tests.yml/badge.svg?branch=master" alt="Tests status"></a>
</p>
<p>Install and use compiled <a href="https://github.com/DirectoryTree/PrivacyFilterBinaries"><code>privacy-filter.cpp</code></a> binaries from Laravel applications.</p>
</div>

## Introduction

Privacy Filter provides a Laravel wrapper around the `privacy-filter.cpp` command line binary. It installs [the compiled binary](https://github.com/DirectoryTree/PrivacyFilterBinaries) for the current operating system, downloads the GGUF model used by the binary, and exposes a small PHP API for detecting private entities in text.

## Installation

You may install the package via Composer:

```bash
composer require directorytree/privacy-filter
```

After installing the package, run the `privacy-filter:install` Artisan command. This command will install both the compiled binary and the GGUF model required by the runtime API:

```bash
php artisan privacy-filter:install
```

If either file already exists, the installer will leave it in place. You may use the `--force` option to overwrite the installed files:

```bash
php artisan privacy-filter:install --force
```

## Configuration

You may publish the package configuration file using the `vendor:publish` Artisan command:

```bash
php artisan vendor:publish --tag=privacy-filter-config
```

The published configuration file allows you to customize the installed binary path, model path, process timeout, model URL, and binary release source:

```php
'paths' => [
    'binary' => env('PRIVACY_FILTER_BINARY_PATH', storage_path('app/privacy-filter/bin/privacy-filter')),
    'model' => env('PRIVACY_FILTER_MODEL_PATH', storage_path('app/privacy-filter/models/privacy-filter-f16.gguf')),
],

'process' => [
    'timeout' => (float) env('PRIVACY_FILTER_TIMEOUT', 60),
],

'model' => [
    'url' => env('PRIVACY_FILTER_MODEL_URL', 'https://huggingface.co/LocalAI-io/privacy-filter-GGUF/resolve/main/privacy-filter-f16.gguf'),
],

'release' => [
    'repository' => env('PRIVACY_FILTER_BINARY_REPOSITORY', 'DirectoryTree/PrivacyFilterBinaries'),
    'version' => env('PRIVACY_FILTER_BINARY_VERSION', 'v1.0.0'),
],
```

## Installing Assets

The `privacy-filter:install` command installs all assets required by the package:

```bash
php artisan privacy-filter:install
```

You may install the binary and model independently if you need more control over deployment:

```bash
php artisan privacy-filter:install-binary
php artisan privacy-filter:install-model
```

The binary installer downloads the correct archive for the current operating system from the configured GitHub release. You may install a different release or provide a direct archive URL:

```bash
php artisan privacy-filter:install-binary --release=v1.0.0
php artisan privacy-filter:install-binary --url=https://example.com/privacy-filter-darwin-arm64.tar.gz
```

The model installer downloads the configured GGUF model. You may also provide a direct model URL:

```bash
php artisan privacy-filter:install-model --url=https://example.com/privacy-filter.gguf
```

## Usage

You may classify text using the `PrivacyFilter` facade. The `entities` method returns an array of `Entity` instances:

```php
use DirectoryTree\PrivacyFilter\Facades\PrivacyFilter;

$entities = PrivacyFilter::entities('Contact John Doe at jdoe@example.com.');

/** @var \DirectoryTree\PrivacyFilterClassifier\Entity $entity */
foreach ($entities as $entity) {
    $entity->type;  // private_email
    $entity->text;  // jdoe@example.com
    $entity->start; // 20
    $entity->end;   // 36
    $entity->score; // 0.98
}
```

Each entity contains the detected type, original text, byte offsets, and confidence score. You may also retrieve the byte length of the entity:

```php
$length = $entity->length();
```

## Thresholds

The classifier uses a default threshold of `0.5`. You may provide a threshold at runtime when classifying text:

```php
$entities = PrivacyFilter::entities(
    text: 'Contact John Doe at jdoe@example.com.',
    threshold: 0.75,
);
```

## Entity Types

The raw entity type is available through the entity's `type` property:

```php
$entity->type;
```

For known privacy-filter entity types, you may retrieve the matching `EntityType` enum instance:

```php
use DirectoryTree\PrivacyFilterClassifier\EntityType;

if ($entity->type() === EntityType::PrivateEmail) {
    // ...
}
```

If the binary returns an entity type that is not known by this package, the `type` method will return `null`.

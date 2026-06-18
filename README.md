<div align="center">
<h1>Privacy Filter</h1>
<p>
<a href="https://github.com/DirectoryTree/PrivacyFilter/actions/workflows/run-tests.yml"><img src="https://github.com/DirectoryTree/PrivacyFilter/actions/workflows/run-tests.yml/badge.svg?branch=master" alt="Tests status"></a>
</p>
<p>Laravel wrapper for installing and using the compiled <a href="https://github.com/localai-org/privacy-filter.cpp"><code>privacy-filter.cpp</code></a> binaries.</p>
</div>

## Installation

```bash
composer require directorytree/privacy-filter
```

## Artisan Commands

```bash
php artisan privacy-filter:install
php artisan privacy-filter:install-binary
php artisan privacy-filter:install-model
```

## Usage

```php
use DirectoryTree\PrivacyFilter\Facades\PrivacyFilter;

$entities = PrivacyFilter::entities('Contact John Doe at jdoe@example.com.');

foreach ($entities as $entity) {
    $entity->type;  // email
    $entity->text;  // jdoe@example.com
    $entity->start; // byte offset
    $entity->end;   // byte offset
    $entity->score; // confidence score
}
```

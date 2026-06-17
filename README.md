# Privacy Filter

Laravel wrapper for installing and using the compiled `privacy-filter.cpp` binaries.

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

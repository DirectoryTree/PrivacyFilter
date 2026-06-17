<?php

use DirectoryTree\PrivacyFilter\Entity;
use DirectoryTree\PrivacyFilter\Exceptions\BinaryNotFoundException;
use DirectoryTree\PrivacyFilter\Exceptions\ModelNotFoundException;
use DirectoryTree\PrivacyFilter\Facades\PrivacyFilter as PrivacyFilterFacade;
use DirectoryTree\PrivacyFilter\Classifier;

beforeEach(function () {
    putenv('PRIVACY_FILTER_FAKE_MODE');
    putenv('PRIVACY_FILTER_FAKE_NEEDLE');
    putenv('PRIVACY_FILTER_FAKE_TYPE');

    $this->binaryPath = realpath(__DIR__.'/../Fixtures/privacy-filter');
    $this->modelPath = sys_get_temp_dir().'/privacy-filter-test-model.gguf';

    chmod($this->binaryPath, 0755);
    file_put_contents($this->modelPath, 'model');

    config()->set('privacy-filter.paths.binary', $this->binaryPath);
    config()->set('privacy-filter.paths.model', $this->modelPath);
    config()->set('privacy-filter.model.threshold', 0.5);
    config()->set('privacy-filter.process.timeout', 5);

    $this->app->forgetInstance(Classifier::class);
});

afterEach(function () {
    putenv('PRIVACY_FILTER_FAKE_MODE');
    putenv('PRIVACY_FILTER_FAKE_NEEDLE');
    putenv('PRIVACY_FILTER_FAKE_TYPE');

    unset(
        $_ENV['PRIVACY_FILTER_FAKE_MODE'],
        $_ENV['PRIVACY_FILTER_FAKE_NEEDLE'],
        $_ENV['PRIVACY_FILTER_FAKE_TYPE'],
        $_SERVER['PRIVACY_FILTER_FAKE_MODE'],
        $_SERVER['PRIVACY_FILTER_FAKE_NEEDLE'],
        $_SERVER['PRIVACY_FILTER_FAKE_TYPE'],
    );

    @unlink($this->modelPath);
});

it('returns entity instances from the privacy filter output', function () {
    $entities = app(Classifier::class)->entities(
        'Contact John Doe at jdoe@example.com from 555-0100.',
    );

    expect($entities)->toHaveCount(1)
        ->and($entities[0])->toBeInstanceOf(Entity::class)
        ->and($entities[0]->type)->toBe('email')
        ->and($entities[0]->text)->toBe('jdoe@example.com')
        ->and($entities[0]->start)->toBe(20)
        ->and($entities[0]->end)->toBe(36)
        ->and($entities[0]->score)->toBe(0.9876)
        ->and($entities[0]->length())->toBe(16);
});

it('can be called through the facade', function () {
    $entities = PrivacyFilterFacade::entities(
        'Contact John Doe at jdoe@example.com from 555-0100.',
    );

    expect($entities)->toHaveCount(1)
        ->and($entities[0]->text)->toBe('jdoe@example.com');
});

it('uses byte offsets to hydrate text when the cli text field is not valid json', function () {
    foreach ([
        'PRIVACY_FILTER_FAKE_MODE' => 'raw-text',
        'PRIVACY_FILTER_FAKE_NEEDLE' => 'John "JD" Doe',
        'PRIVACY_FILTER_FAKE_TYPE' => 'person',
    ] as $key => $value) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    $entities = app(Classifier::class)->entities('Contact John "JD" Doe today.');

    expect($entities)->toHaveCount(1)
        ->and($entities[0]->type)->toBe('person')
        ->and($entities[0]->text)->toBe('John "JD" Doe');
});

it('throws an exception when the binary does not exist', function () {
    config()->set('privacy-filter.paths.binary', __DIR__.'/missing-privacy-filter');

    $this->app->forgetInstance(Classifier::class);

    app(Classifier::class)->entities('Contact John Doe at jdoe@example.com.');
})->throws(BinaryNotFoundException::class);

it('throws an exception when the model does not exist', function () {
    config()->set('privacy-filter.paths.model', __DIR__.'/missing-model.gguf');

    $this->app->forgetInstance(Classifier::class);

    app(Classifier::class)->entities('Contact John Doe at jdoe@example.com.');
})->throws(ModelNotFoundException::class);

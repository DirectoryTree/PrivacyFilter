<?php

use DirectoryTree\PrivacyFilter\Facades\PrivacyFilter;
use DirectoryTree\PrivacyFilter\Testing\PrivacyFilterFake;
use DirectoryTree\PrivacyFilterClassifier\Entity;

it('returns fake entities through the facade', function () {
    $entity = new Entity(
        type: 'private_email',
        start: 20,
        end: 36,
        score: 0.98,
        text: 'jdoe@example.com',
    );

    $fake = PrivacyFilter::fake([$entity]);

    expect($fake)->toBeInstanceOf(PrivacyFilterFake::class)
        ->and(PrivacyFilter::entities('Contact John Doe at jdoe@example.com.'))->toBe([$entity]);
});

it('returns fake entities matching exact text', function () {
    $entity = new Entity(
        type: 'private_person',
        start: 8,
        end: 16,
        score: 0.95,
        text: 'John Doe',
    );

    PrivacyFilter::fake([
        'Contact John Doe.' => [$entity],
    ]);

    expect(PrivacyFilter::entities('Contact John Doe.'))->toBe([$entity])
        ->and(PrivacyFilter::entities('Contact Jane Doe.'))->toBe([]);
});

it('returns fake entities matching partial text patterns', function () {
    $entity = new Entity(
        type: 'private_email',
        start: 20,
        end: 36,
        score: 0.98,
        text: 'jdoe@example.com',
    );

    PrivacyFilter::fake([
        '*jdoe@example.com*' => [$entity],
    ]);

    expect(PrivacyFilter::entities('Contact John Doe at jdoe@example.com.'))->toBe([$entity]);
});

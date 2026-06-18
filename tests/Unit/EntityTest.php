<?php

use DirectoryTree\PrivacyFilter\Entity;
use DirectoryTree\PrivacyFilter\EntityType;

it('exposes entity details as arrays and json', function () {
    $entity = new Entity(
        type: 'email',
        start: 20,
        end: 36,
        score: 0.9876,
        text: 'jdoe@example.com',
    );

    expect($entity->length())->toBe(16)
        ->and($entity->toArray())->toBe([
            'type' => 'email',
            'start' => 20,
            'end' => 36,
            'score' => 0.9876,
            'text' => 'jdoe@example.com',
        ])
        ->and($entity->jsonSerialize())->toBe($entity->toArray());
});

it('returns the known entity type enum', function () {
    $entity = new Entity(
        type: 'private_email',
        start: 20,
        end: 36,
        score: 0.9876,
        text: 'jdoe@example.com',
    );

    expect($entity->type())->toBe(EntityType::PrivateEmail);
});

it('returns null when the entity type is unknown', function () {
    $entity = new Entity(
        type: 'private_custom_identifier',
        start: 20,
        end: 36,
        score: 0.9876,
        text: 'ABC-123',
    );

    expect($entity->type())->toBeNull();
});

<?php

use DirectoryTree\PrivacyFilter\Entity;

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

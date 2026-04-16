<?php

use Aleoosha\HiveMind\DTO\NodeMetrics;

test('it stores metrics correctly', function () {
    $metrics = new NodeMetrics(
        cpu: 25.5,
        memory: 60.0,
        timestamp: 1713250000.0
    );

    expect($metrics->cpu)->toBe(25.5)
        ->and($metrics->memory)->toBe(60.0)
        ->and($metrics->timestamp)->toBe(1713250000.0);
});

test('it can convert to array', function () {
    $metrics = new NodeMetrics(10.0, 20.0, 12345.0);
    
    expect($metrics->toArray())
        ->toBeArray()
        ->toHaveKey('cpu', 10.0);
});

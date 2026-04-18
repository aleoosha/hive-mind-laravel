<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Unit;

use Aleoosha\HiveMind\DTO\NodeMetrics;

test('it stores metrics correctly', function () {
    $timestamp = time();
    $metrics = new NodeMetrics(
        cpu: 45.5,
        memory: 70.2,
        dbLatency: 12.5,     // Тот самый аргумент #3
        apiLatency: 150.0,
        timestamp: $timestamp,
        nodeId: 'test-node-1'
    );

    expect($metrics->cpu)->toBe(45.5)
        ->and($metrics->memory)->toBe(70.2)
        ->and($metrics->dbLatency)->toBe(12.5)
        ->and($metrics->apiLatency)->toBe(150.0)
        ->and($metrics->timestamp)->toBe($timestamp)
        ->and($metrics->nodeId)->toBe('test-node-1');
});

test('it can convert to array', function () {
    $metrics = new NodeMetrics(
        cpu: 10.0,
        memory: 20.0,
        dbLatency: 5.0,
        apiLatency: 0.0,
        timestamp: 123456789,
        nodeId: 'node-a'
    );

    $array = $metrics->toArray();

    expect($array)->toBe([
        'cpu' => 10.0,
        'memory' => 20.0,
        'db_latency' => 5.0,
        'api_latency' => 0.0,
        'timestamp' => 123456789,
        'node_id' => 'node-a',
    ]);
});

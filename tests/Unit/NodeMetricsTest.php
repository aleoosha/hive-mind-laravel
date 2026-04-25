<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Unit;

use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;
use Aleoosha\Support\Types\FixedPoint;

test('it stores metrics correctly using FixedPoint', function () {
    $timestamp = (int)(microtime(true) * 1000);
    
    // In the new architecture, we use FixedPoint objects for all numeric values
    $metrics = new NodeMetrics(
        cpu: FixedPoint::fromFloat(45.5),
        memory: FixedPoint::fromFloat(70.2),
        dbLatency: FixedPoint::fromFloat(12.5),
        apiLatency: FixedPoint::fromFloat(150.0),
        timestampMs: $timestamp,
        nodeId: 'test-node-1'
    );

    expect($metrics->cpu->toFloat())->toBe(45.5)
        ->and($metrics->memory->toFloat())->toBe(70.2)
        ->and($metrics->dbLatency->toFloat())->toBe(12.5)
        ->and($metrics->apiLatency->toFloat())->toBe(150.0)
        ->and($metrics->timestampMs)->toBe($timestamp)
        ->and($metrics->nodeId)->toBe('test-node-1');
});

test('it ensures internal integrity of FixedPoint values', function () {
    $metrics = new NodeMetrics(
        cpu: FixedPoint::fromInt(10), // Should be 10000 internally
        memory: FixedPoint::fromInt(20),
        dbLatency: FixedPoint::fromInt(5),
        apiLatency: new FixedPoint(0),
        timestampMs: 123456789,
        nodeId: 'node-a'
    );

    // We verify that the scale (1000) is preserved
    expect($metrics->cpu->value)->toBe(10000)
        ->and($metrics->dbLatency->value)->toBe(5000)
        ->and($metrics->nodeId)->toBe('node-a');
});

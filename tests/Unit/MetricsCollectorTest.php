<?php

use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\DTO\NodeMetrics;

test('it collects metrics and returns a NodeMetrics object', function () {
    $collector = new MetricsCollector();
    $metrics = $collector->getMetrics();

    expect($metrics)->toBeInstanceOf(NodeMetrics::class)
        ->and($metrics->cpu)->toBeGreaterThanOrEqual(0)
        ->and($metrics->memory)->toBeGreaterThanOrEqual(0);
});

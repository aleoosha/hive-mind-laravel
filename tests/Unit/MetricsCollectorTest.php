<?php

declare(strict_types=1);

use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Illuminate\Support\Facades\DB;

test('it collects metrics and returns a NodeMetrics object', function () {
    $collector = new MetricsCollector();
    $metrics = $collector->getMetrics();

    expect($metrics)->toBeInstanceOf(NodeMetrics::class)
        ->and($metrics->cpu)->toBeGreaterThanOrEqual(0)
        ->and($metrics->memory)->toBeGreaterThanOrEqual(0);
});

test('metrics collector captures db latency', function () {
    $collector = new MetricsCollector();
    
    DB::select('SELECT 1');

    $metrics = $collector->getMetrics();
    
    expect($metrics->dbLatency)->toBeGreaterThan(0);
});
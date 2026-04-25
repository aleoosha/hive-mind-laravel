<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Unit;

use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;
use Aleoosha\Support\Types\FixedPoint;
use Illuminate\Support\Facades\DB;

test('it collects metrics and returns a NodeMetrics object', function () {
    $collector = new MetricsCollector();
    
    // In new architecture we use collect() instead of getMetrics()
    $metrics = $collector->collect();

    expect($metrics)->toBeInstanceOf(NodeMetrics::class)
        ->and($metrics->cpu)->toBeInstanceOf(FixedPoint::class)
        ->and($metrics->cpu->toFloat())->toBeGreaterThanOrEqual(0.0)
        ->and($metrics->memory->toFloat())->toBeGreaterThanOrEqual(0.0);
});

test('metrics collector captures db latency', function () {
    $collector = new MetricsCollector();
    
    // Trigger a real DB query to check the listener
    DB::select('SELECT 1');
    
    $metrics = $collector->collect();

    // dbLatency is now a FixedPoint object
    expect($metrics->dbLatency->toFloat())->toBeGreaterThan(0.0);
});

<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\DssCore\DTO\DecisionResult;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\HiveMind\Http\Middleware\AltruismMiddleware;
use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;
use Aleoosha\Telemetry\Contracts\StateRepositoryInterface;
use Illuminate\Http\Request;
use Mockery;

test('middleware sheds traffic when intelligence signals danger', function () {
    $now = (int) (microtime(true) * 1000);
    $zero = new FixedPoint(0);

    // 1. Mock State Repository (Cluster-wide telemetry)
    $stateRepo = Mockery::mock(StateRepositoryInterface::class);
    $stateRepo->shouldReceive('updateLocal')->once();

    // 2. Mock PID State Repository (History for decision)
    $pidRepo = Mockery::mock(PidStateRepositoryInterface::class);
    $pidRepo->shouldReceive('getState')->andReturn(null);

    // 3. Mock Collector (Eyes)
    $collector = Mockery::mock(MetricsCollectorInterface::class);
    $collector->shouldReceive('collect')->andReturn(new NodeMetrics(
        cpu: FixedPoint::fromFloat(90.0),
        memory: FixedPoint::fromFloat(90.0),
        dbLatency: FixedPoint::fromFloat(500.0),
        apiLatency: $zero,
        timestampMs: $now,
        nodeId: 'test-node'
    ));

    // 4. Mock Decision Engine (Brain) - setting 100% shedding rate
    $engine = Mockery::mock(DecisionEngine::class);
    $engine->shouldReceive('evaluate')->andReturn(new DecisionResult(
        systemLoad: FixedPoint::fromFloat(0.9),
        sheddingRate: FixedPoint::fromFloat(1.0), // 100% drop chance
        alerts: ['cpu_percent'],
        timestampMs: $now
    ));

    // 5. Construct Middleware with new dependencies
    $middleware = new AltruismMiddleware($stateRepo, $pidRepo, $collector, $engine);

    // 6. Assert that HiveOvercapacityException is thrown
    expect(fn () => $middleware->handle(Request::create('/api/test'), fn () => response('ok')))
        ->toThrow(HiveOvercapacityException::class);
});

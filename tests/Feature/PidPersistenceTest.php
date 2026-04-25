<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\DTO\FixedPidResult;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;
use Mockery;

/**
 * Feature test for PID state persistence and adaptive tuning logic.
 * Ensures that the DecisionEngine correctly stores and retrieves
 * tuned coefficients between execution cycles.
 */
test('decision engine persists and retrieves tuned coefficients', function () {
    // 1. Force the config thresholds for the test context
    config(['hive-mind.thresholds' => [
        'cpu_percent' => 80 // or 0.8 depending on your config style
    ]]);

    $zero = new FixedPoint(0);
    $now = (int)(microtime(true) * 1000);

    // Initial state: Kp = 0.6, lastError was negative (-0.2)
    $initialResult = new FixedPidResult(
        output: $zero,
        lastError: FixedPoint::fromFloat(-0.2), 
        integral: $zero,
        timestampMs: $now - 1000,
        kp: FixedPoint::fromFloat(0.6),
        ki: FixedPoint::fromFloat(0.1),
        kd: FixedPoint::fromFloat(0.4)
    );

    $repo = Mockery::mock(PidStateRepositoryInterface::class);
    
    // Default behaviors
    $repo->shouldReceive('getState')->byDefault()->andReturn(null);
    $repo->shouldReceive('saveState')->byDefault();

    // 2. Mock getState to return our initial result when ANY key containing 'cpu' is asked
    $repo->shouldReceive('getState')
        ->with(Mockery::on(fn($key) => str_contains($key, 'cpu')))
        ->andReturn($initialResult);

    // 3. Catch the saving of the NEW state
    $repo->shouldReceive('saveState')
        ->atLeast()->once()
        ->withArgs(function ($key, $result) {
            if (str_contains($key, 'cpu')) {
                // The main check: Kp must be reduced due to resonance detection
                // (current error is positive, last was negative)
                return $result->kp->toFloat() < 0.6;
            }
            return true;
        });

    $this->app->instance(PidStateRepositoryInterface::class, $repo);

    $engine = app(DecisionEngine::class);

    // Current metrics: CPU 95% (Positive error)
    $metrics = new NodeMetrics(
        cpu: FixedPoint::fromFloat(95.0),
        memory: $zero, dbLatency: $zero, apiLatency: $zero,
        timestampMs: $now, nodeId: 'test-node'
    );

    $engine->evaluate($metrics, $initialResult);
});


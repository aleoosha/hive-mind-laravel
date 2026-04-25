<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\DTO\FixedPidResult;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;
use Mockery;

/**
 * Feature test for PID state persistence and adaptive tuning logic.
 */
test('decision engine persists and retrieves tuned coefficients', function () {
    // 1. Force the config thresholds for the test context
    config(['hive-mind.thresholds' => [
        'cpu_percent' => [
            'limit' => 80,
            'activation_margin' => 0.9,
            'settling_time' => 5,
        ]
    ]]);

    $zero = new FixedPoint(0);
    $now = (int)(microtime(true) * 1000);

    // Initial state: lastError was negative (-0.2) to trigger resonance tuning
    $initialResult = new FixedPidResult(
        output: $zero,
        lastError: FixedPoint::fromFloat(-0.2), 
        integral: $zero,
        timestampMs: $now - 1000,
        kp: FixedPoint::fromFloat(2.0), // Base Kp for 5s settling time (10/5)
        ki: FixedPoint::fromFloat(0.5),
        kd: FixedPoint::fromFloat(1.0)
    );

    $repo = Mockery::mock(PidStateRepositoryInterface::class);
    
    // Default behaviors
    $repo->shouldReceive('getState')->byDefault()->andReturn(null);
    $repo->shouldReceive('saveState')->byDefault();

    // 2. Mock getState to return our initial result for CPU
    $repo->shouldReceive('getState')
        ->with(Mockery::on(fn($key) => str_contains($key, 'cpu')))
        ->andReturn($initialResult);

    // 3. Catch the saving of the NEW state
    $repo->shouldReceive('saveState')
        ->atLeast()->once()
        ->withArgs(function ($key, $result) {
            if (str_contains($key, 'cpu')) {
                // Resonance detection: sign change (-0.2 to positive error) 
                // should reduce Kp (initial 2.0 -> tuned < 2.0)
                return $result->kp->toFloat() < 2.0;
            }
            return true;
        });

    $this->app->instance(PidStateRepositoryInterface::class, $repo);

    /** @var DecisionEngine $engine */
    $engine = app(DecisionEngine::class);

    // Current metrics: CPU 95 (Exceeds threshold 80 and activation 72)
    $metrics = new NodeMetrics(
        cpu: FixedPoint::fromFloat(95.0),
        memory: $zero, 
        dbLatency: $zero, 
        apiLatency: $zero,
        timestampMs: $now, 
        nodeId: 'test-node'
    );

    $engine->evaluate($metrics);
});

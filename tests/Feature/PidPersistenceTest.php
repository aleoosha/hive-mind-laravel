<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\DTO\FixedPidResult;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Aleoosha\HiveMind\Support\FixedPoint;
use Mockery;

test('swarm intelligence persists and retrieves tuned coefficients', function () {
    $repo = Mockery::mock(PidStateRepository::class);
    $zero = FixedPoint::raw(0);

    $initialResult = new FixedPidResult(
        output: $zero,
        lastError: FixedPoint::fromFloat(-0.2),
        integral: $zero,
        timestamp: microtime(true),
        kp: FixedPoint::fromFloat(0.6),
        ki: FixedPoint::fromFloat(0.1),
        kd: FixedPoint::fromFloat(0.4)
    );

    $repo->shouldReceive('getState')->andReturn($initialResult);

    $repo->shouldReceive('saveState')->atLeast()->once()->withArgs(function ($metric, $result) {
        if ($metric === 'cpu_percent') {
            return $result->kp->toFloat() < 0.6;
        }

        return true;
    });

    $this->app->instance(PidStateRepository::class, $repo);

    /** @var SwarmIntelligence $intelligence */
    $intelligence = app(SwarmIntelligence::class);

    $metrics = new NodeMetrics(
        cpu: 95.0,
        memory: 50.0,
        dbLatency: 0.0,
        apiLatency: 0.0,
        timestamp: (int) time(),
        nodeId: 'test-node'
    );

    $intelligence->computeSheddingRate($metrics);
});

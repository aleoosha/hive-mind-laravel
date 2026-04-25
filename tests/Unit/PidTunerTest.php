<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Unit;

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Contracts\DTO\FixedPidResult;
use Aleoosha\TauPid\Kernel\Services\PidTuner;

test('it reduces Kp when resonance is detected', function () {
    $tuner = new PidTuner();
    $base = new PidSettings(
        kp: FixedPoint::fromFloat(0.6), 
        ki: FixedPoint::fromFloat(0.1), 
        kd: FixedPoint::fromFloat(0.4), 
        antiWindup: FixedPoint::fromInt(20)
    );

    $lastResult = new FixedPidResult(
        output: new FixedPoint(0),
        lastError: FixedPoint::fromFloat(-0.2), // Error was negative
        integral: new FixedPoint(0),
        timestampMs: (int)(microtime(true) * 1000),
        kp: FixedPoint::fromFloat(0.6),
        ki: FixedPoint::fromFloat(0.1),
        kd: FixedPoint::fromFloat(0.4)
    );

    $currentError = FixedPoint::fromFloat(0.2); // Error is now positive -> Sign Change!
    
    $tuned = $tuner->tune($base, $currentError, $lastResult);

    // Kp should decrease (0.6 * 0.9 = 0.54)
    expect($tuned->kp->toFloat())->toBeLessThan(0.6)
        ->and($tuned->kp->toFloat())->toBe(0.54);
});

test('it increases Ki when static error persists', function () {
    $tuner = new PidTuner();
    $base = new PidSettings(
        kp: FixedPoint::fromFloat(0.6), 
        ki: FixedPoint::fromFloat(0.1), 
        kd: FixedPoint::fromFloat(0.4), 
        antiWindup: FixedPoint::fromInt(20)
    );

    // Error stuck at 0.2
    $lastResult = new FixedPidResult(
        output: new FixedPoint(0),
        lastError: FixedPoint::fromFloat(0.2),
        integral: new FixedPoint(0),
        timestampMs: (int)(microtime(true) * 1000),
        kp: FixedPoint::fromFloat(0.6),
        ki: FixedPoint::fromFloat(0.1),
        kd: FixedPoint::fromFloat(0.4)
    );

    // New error almost the same (diff < 0.05) -> Stagnation!
    $currentError = FixedPoint::fromFloat(0.201);
    
    $tuned = $tuner->tune($base, $currentError, $lastResult);

    // Ki should increase (0.1 + 0.05 = 0.15)
    expect($tuned->ki->toFloat())->toBeGreaterThan(0.1)
        ->and($tuned->ki->toFloat())->toBe(0.15);
});

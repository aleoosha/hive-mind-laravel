<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Unit;

use Aleoosha\HiveMind\Services\PidTuner;
use Aleoosha\HiveMind\DTO\PidSettings;
use Aleoosha\HiveMind\DTO\FixedPidResult;
use Aleoosha\HiveMind\Support\FixedPoint;

test('it reduces Kp when resonance is detected', function () {
    $tuner = new PidTuner();
    $base = new PidSettings(kp: 0.6, ki: 0.1, kd: 0.4, antiWindup: 20.0);
    
    $lastResult = new FixedPidResult(
        output:    FixedPoint::raw(0), 
        lastError: FixedPoint::fromFloat(-0.2), 
        integral:  FixedPoint::raw(0), 
        timestamp: microtime(true), 
        kp: FixedPoint::fromFloat(0.6), 
        ki: FixedPoint::fromFloat(0.1), 
        kd: FixedPoint::fromFloat(0.4)
    );
    
    $currentError = 0.2;
    $tuned = $tuner->tune($base, $lastResult, $currentError);

    // Kp должен снизиться (был 0.6, станет меньше)
    expect($tuned->kp)->toBeLessThan(0.6);
});

test('it increases Ki when static error persists', function () {
    $tuner = new PidTuner();
    $base = new PidSettings(0.6, 0.1, 0.4, 20.0);
    
    // Ошибка замерла на 0.2
    $lastResult = new FixedPidResult(
        output:    FixedPoint::raw(0), 
        lastError: FixedPoint::fromFloat(0.2), 
        integral:  FixedPoint::raw(0), 
        timestamp: microtime(true), 
        kp: FixedPoint::fromFloat(0.6), 
        ki: FixedPoint::fromFloat(0.1), 
        kd: FixedPoint::fromFloat(0.4)
    );
    
    // Новая ошибка почти такая же (разница < 0.05) -> Застой!
    $currentError = 0.201;
    $tuned = $tuner->tune($base, $lastResult, $currentError);

    // Ki должен вырасти (был 0.1, станет больше)
    expect($tuned->ki)->toBeGreaterThan(0.1);
});

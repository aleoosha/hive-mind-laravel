<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Unit;

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Kernel\Services\PidCalculator;

test('pid calculator reacts to sudden spikes (D-term)', function () {
    // 1. Setup the calculator from the Kernel library
    $calculator = new PidCalculator();
    
    // 2. Setup settings with ONLY Derivative gain (D-term)
    $settings = new PidSettings(
        kp: new FixedPoint(0),
        ki: new FixedPoint(0),
        kd: FixedPoint::fromFloat(0.5),
        antiWindup: FixedPoint::fromInt(20)
    );

    $now = (int)(microtime(true) * 1000);

    // Initial state: system is at setpoint (error 0)
    $res1 = $calculator->calculate(
        error: new FixedPoint(0), 
        deltaTimeMs: 0, 
        previousState: null, 
        settings: $settings
    );

    // Sudden spike: error becomes +50% (500 in FixedPoint) after 100ms
    $error = FixedPoint::fromFloat(0.5);
    $res2 = $calculator->calculate(
        error: $error, 
        deltaTimeMs: 100, 
        previousState: $res1, 
        settings: $settings
    );

    // 3. Assertions
    // Output should be maxed out (1.0) because of the sudden jump
    expect($res2->output->toFloat())->toBe(1.0); 
});

<?php

declare(strict_types=1);

use Aleoosha\HiveMind\Services\PidCalculator;
use Aleoosha\HiveMind\DTO\PidSettings;

test('pid calculator reacts to sudden spikes (D-term)', function () {
    $calculator = new \Aleoosha\HiveMind\Services\PidCalculator();
    $settings = new \Aleoosha\HiveMind\DTO\PidSettings(
        kp: 0.0, ki: 0.0, kd: 0.5, antiWindup: 20.0
    );

    $now = microtime(true);
    
    $res1 = $calculator->calculate($settings, 100.0, 110.0, 0.0, 0.0, null);

    $res2 = $calculator->calculate(
        $settings, 
        100.0, 
        150.0, 
        $res1->lastError->toFloat(), 
        $res1->integral->toFloat(), 
        $now - 0.1
    );

    expect($res2->output->toFloat())->toBeGreaterThan(0)
        ->and($res2->output->toFloat())->toBe(100.0);
});

<?php

declare(strict_types=1);

use Aleoosha\HiveMind\Services\PidCalculator;
use Aleoosha\HiveMind\DTO\PidSettings;

test('pid calculator reacts to sudden spikes (D-term)', function () {
    $calculator = new PidCalculator();
    $settings = new PidSettings(kp: 0.5, ki: 0.1, kd: 2.0); // Высокий KD для теста паники
    $target = 100.0;

    // Первый замер - норма
    $result1 = $calculator->calculate($settings, $target, 100.0, 0.0, 0.0, microtime(true));
    
    // Резкий скачок через долю секунды
    usleep(100000);
    $result2 = $calculator->calculate($settings, $target, 150.0, $result1->lastError, $result1->integral, $result1->timestamp);

    // Ожидаем высокий output из-за скорости роста (дифференциала)
    expect($result2->output)->toBeGreaterThan(0);
});

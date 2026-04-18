<?php

declare(strict_types=1);

use Aleoosha\HiveMind\Services\PidTuner;
use Aleoosha\HiveMind\DTO\PidSettings;
use Aleoosha\HiveMind\DTO\PidResult;

test('it reduces Kp when resonance is detected', function () {
    $tuner = new PidTuner();
    $base = new PidSettings(kp: 0.6, ki: 0.1, kd: 0.4, antiWindup: 20.0);
    
    // Последний результат говорит, что мы использовали Kp = 0.6
    $lastResult = new PidResult(
        output: 0, 
        lastError: -0.2, // Было -20% (недолет)
        integral: 0, 
        timestamp: microtime(true), 
        kp: 0.6, ki: 0.1, kd: 0.4
    );
    
    // Сейчас ошибка +0.2 (перелет) -> явная раскачка
    $tuned = $tuner->tune($base, $lastResult, 0.2);

    // Теперь 0.54 < 0.6 должно пройти
    expect($tuned->kp)->toBeLessThan(0.6);
});

test('it increases Ki when static error persists', function () {
    $tuner = new PidTuner();
    $base = new PidSettings(0.6, 0.1, 0.4, 20.0);
    
    // В прошлом шаге использовали Ki = 0.1
    $lastResult = new PidResult(
        output: 0, 
        lastError: 0.2, 
        integral: 0, 
        timestamp: microtime(true), 
        kp: 0.6, 
        ki: 0.1, // Текущий Ki
        kd: 0.4
    );
    
    // Новая ошибка почти такая же (разница 0.001)
    $tuned = $tuner->tune($base, $lastResult, 0.201);

    // Ожидаем 0.15
    expect($tuned->ki)->toBeGreaterThan(0.1);
});


<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Aleoosha\HiveMind\DTO\PidResult;

test('swarm intelligence persists and retrieves tuned coefficients', function () {
    $intelligence = app(SwarmIntelligence::class);
    $repo = app(PidStateRepository::class);

    // 1. Создаем "прошлое" в Redis: недолет (ошибка -0.2)
    $pastResult = new PidResult(0, -0.2, 0, microtime(true), 0.6, 0.1, 0.4);
    $repo->saveState('cpu_percent', $pastResult);

    // 2. Имитируем текущую перегрузку: перелет (CPU 95% при пороге 80% -> ошибка +0.18)
    $metrics = new NodeMetrics(95.0, 50.0, 0.0, 0.0, (int)time(), 'test-node');

    // 3. Вызываем расчет. Тюнер видит смену знака (-0.2 -> +0.18) и снижает Kp
    $intelligence->computeSheddingRate($metrics);

    // 4. Проверяем, что в Redis теперь лежит обученный Kp
    $updatedState = $repo->getState('cpu_percent');

    expect($updatedState->kp)->toBeGreaterThan(0)
        ->and($updatedState->kp)->toBeLessThan(0.6); // Должен снизиться от базы
});


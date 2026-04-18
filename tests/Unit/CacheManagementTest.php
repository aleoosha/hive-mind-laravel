<?php

declare(strict_types=1);

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\Contracts\Serializer;
use Illuminate\Support\Facades\Redis;

test('it flushes local cache and forces fresh redis lookup', function () {
    $serializer = app(Serializer::class);
    $repository = app(StateRepository::class);

    config(['hive-mind.thresholds.cpu_percent' => 100]);
    config(['hive-mind.thresholds.memory_percent' => 100]);

    $nodeKey = 'hive_node:1';
    $time = time();

    // 1. Первая порция данных (Stress 20)
    $metrics1 = new NodeMetrics(20.0, 10.0, 0.0, 0.0, $time, 'node-1');
    $data1 = $serializer->pack($metrics1->toArray());

    // 2. Вторая порция данных (Stress 80)
    $metrics2 = new NodeMetrics(80.0, 10.0, 0.0, 0.0, $time, 'node-1');
    $data2 = $serializer->pack($metrics2->toArray());

    // Настраиваем Redis на последовательный возврат разных данных
    Redis::shouldReceive('keys')->andReturn([$nodeKey]);
    // Первый вызов get вернет data1, второй и последующие - data2
    Redis::shouldReceive('get')->with($nodeKey)->andReturn($data1, $data2);

    // Первый замер - должно быть 20
    expect($repository->getGlobalHealth())->toBe(20);

    // Второй замер БЕЗ очистки - все еще 20 (работает localCache)
    expect($repository->getGlobalHealth())->toBe(20);

    // ОЧИСТКА
    $repository->flushLocalCache();

    // Третий замер ПОСЛЕ очистки - должно стать 80
    expect($repository->getGlobalHealth())->toBe(80);
});

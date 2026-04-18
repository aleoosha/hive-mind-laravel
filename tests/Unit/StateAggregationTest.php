<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Unit;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Illuminate\Support\Facades\Redis;
use Aleoosha\HiveMind\Contracts\Serializer;

test('it calculates average hive health correctly', function () {
    $serializer = app(Serializer::class);
    
    // 1. Устанавливаем пороги в 100, чтобы значения были равны процентам
    config(['hive-mind.thresholds.cpu_percent' => 100]);
    config(['hive-mind.thresholds.memory_percent' => 100]);

    // 2. Создаем ноды. Память ставим минимальной, чтобы max() выбирал CPU
    $node1 = new NodeMetrics(80.0, 10.0, 0.0, 0.0, time(), 'node-1'); // Stress 80
    $node2 = new NodeMetrics(20.0, 10.0, 0.0, 0.0, time(), 'node-2'); // Stress 20

    // 3. Мокаем Redis
    Redis::shouldReceive('keys')->andReturn(['hive_node:1', 'hive_node:2']);
    
    // Важно: возвращаем именно то, что ждет unpack в репозитории
    Redis::shouldReceive('get')->with('hive_node:1')->andReturn($serializer->pack($node1->toArray()));
    Redis::shouldReceive('get')->with('hive_node:2')->andReturn($serializer->pack($node2->toArray()));

    $repository = app(StateRepository::class);

    // 4. Расчет: (80 + 20) / 2 = 50
    expect($repository->getGlobalHealth())->toBe(50);
});

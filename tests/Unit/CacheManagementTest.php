<?php

declare(strict_types=1);

use Aleoosha\HiveMind\Repositories\RedisStateRepository;
use Aleoosha\HiveMind\Contracts\Serializer;
use Illuminate\Support\Facades\Redis;

test('it flushes local cache and forces fresh redis lookup', function () {
    $serializer = Mockery::mock(Serializer::class);
    
    Redis::shouldReceive('keys')->twice()->andReturn(['hive_node:test']);
    Redis::shouldReceive('get')->twice()->andReturn('serialized_data');
    
    $serializer->shouldReceive('unpack')->twice()->andReturn([
        'cpu' => 75,
        'memory' => 0,
        'timestamp' => microtime(true)
    ]);
    
    config(['hive-mind.thresholds.cpu_percent' => 100]);

    $repository = new RedisStateRepository($serializer);

    expect($repository->getGlobalHealth())->toBe(75000);

    expect($repository->getGlobalHealth())->toBe(75000);

    $repository->flushLocalCache();

    expect($repository->getGlobalHealth())->toBe(75000);
});

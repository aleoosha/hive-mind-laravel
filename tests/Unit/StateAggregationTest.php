<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Unit;

use Aleoosha\HiveMind\Repositories\RedisStateRepository;
use Aleoosha\HiveMind\Contracts\Serializer;
use Illuminate\Support\Facades\Redis;
use Mockery;

test('it calculates average hive health correctly', function () {
    $serializer = Mockery::mock(Serializer::class);
    
    Redis::shouldReceive('keys')->once()->andReturn(['hive_node:test']);
    Redis::shouldReceive('get')->once()->andReturn('serialized_data');
    
    $serializer->shouldReceive('unpack')->once()->andReturn([
        'cpu' => 80,
        'memory' => 40,
        'timestamp' => microtime(true)
    ]);
    
    config(['hive-mind.thresholds.cpu_percent' => 80]);
    config(['hive-mind.thresholds.memory_percent' => 80]);

    $repository = new RedisStateRepository($serializer);

    expect($repository->getGlobalHealth())->toBe(100000);
});

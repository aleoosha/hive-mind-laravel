<?php

use Aleoosha\HiveMind\Repositories\RedisStateRepository;
use Aleoosha\HiveMind\Serializers\JsonSerializer;
use Illuminate\Support\Facades\Redis;

test('it calculates average hive health correctly', function () {
    $serializer = new JsonSerializer();
    $repo = new RedisStateRepository($serializer);

    Redis::shouldReceive('keys')->andReturn(['node:1', 'node:2', 'node:3']);
    
    Redis::shouldReceive('get')->andReturn(
        json_encode(['cpu' => 10, 'memory' => 10]),
        json_encode(['cpu' => 50, 'memory' => 50]),
        json_encode(['cpu' => 90, 'memory' => 90])
    );

    expect($repo->getGlobalHealth())->toBe(50);
});

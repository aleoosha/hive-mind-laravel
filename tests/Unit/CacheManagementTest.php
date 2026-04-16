<?php

use Aleoosha\HiveMind\Contracts\Serializer;
use Aleoosha\HiveMind\Repositories\RedisStateRepository;
use Illuminate\Support\Facades\Redis;

test('it flushes local cache and forces fresh redis lookup', function () {
    $serializer = app(Serializer::class);
    $repo = new RedisStateRepository($serializer);

    Redis::shouldReceive('keys')->andReturn(['node:1']);
    Redis::shouldReceive('get')->twice()->andReturn(
        json_encode(['cpu' => 50, 'memory' => 50]),
        json_encode(['cpu' => 90, 'memory' => 90])
    );

    $firstHealth = $repo->getGlobalHealth();
    expect($firstHealth)->toBe(50);

    $secondHealth = $repo->getGlobalHealth();
    expect($secondHealth)->toBe(50);

    $repo->flushLocalCache();

    $thirdHealth = $repo->getGlobalHealth();
    expect($thirdHealth)->toBe(90);
});

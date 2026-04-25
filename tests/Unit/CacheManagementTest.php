<?php declare(strict_types=1);

use Aleoosha\HiveMind\Repositories\RedisStateRepository;
use Aleoosha\Telemetry\Contracts\SerializerInterface;
use Aleoosha\Support\Types\FixedPoint;
use Illuminate\Support\Facades\Redis;

test('it flushes local cache and forces fresh redis lookup', function () {
    // 1. Mock the Serializer (though it might not be used in this specific simple health lookup)
    $serializer = Mockery::mock(SerializerInterface::class);

    // 2. Mock Redis to return a global health value (75.0% -> 75000 in FixedPoint)
    // We expect 2 calls: one for the first lookup, and one after the cache flush
    Redis::shouldReceive('get')
        ->with('hive_global_health')
        ->twice()
        ->andReturn('75000');

    $repository = new RedisStateRepository($serializer);

    // First call: should hit Redis and store in localCache
    expect($repository->getGlobalHealth()->value)->toBe(75000);

    // Second call: should return from localCache (Redis::get won't be called a 3rd time)
    expect($repository->getGlobalHealth()->value)->toBe(75000);

    // Flush the cache
    $repository->flushLocalCache();

    // Third call: should hit Redis again due to flush
    expect($repository->getGlobalHealth()->value)->toBe(75000);
});

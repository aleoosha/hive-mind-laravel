<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Unit;

use Aleoosha\HiveMind\Repositories\RedisStateRepository;
use Aleoosha\Telemetry\Contracts\SerializerInterface;
use Aleoosha\Support\Types\FixedPoint;
use Illuminate\Support\Facades\Redis;
use Mockery;

test('it retrieves global hive health from redis', function () {
    /** @var SerializerInterface|\Mockery\MockInterface $serializer */
    $serializer = Mockery::mock(SerializerInterface::class);

    // В новой архитектуре репозиторий просто читает ключ 'hive_global_health',
    // который записывает команда Pulse. Имитируем 100.0% здоровья (100000 в FixedPoint).
    Redis::shouldReceive('get')
        ->once()
        ->with('hive_global_health')
        ->andReturn('100000');

    $repository = new RedisStateRepository($serializer);
    
    $health = $repository->getGlobalHealth();

    expect($health)->toBeInstanceOf(FixedPoint::class)
        ->and($health->value)->toBe(100000)
        ->and($health->toFloat())->toBe(100.0);
});

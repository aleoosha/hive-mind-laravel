<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\DTO\HardwareContext;
use Aleoosha\HiveMind\Http\Middleware\AltruismMiddleware;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Illuminate\Http\Request;
use Mockery;

test('middleware sheds traffic when intelligence signals danger', function () {
    // 1. Мокаем репозиторий
    $repository = Mockery::mock(StateRepository::class);
    $repository->shouldReceive('updateLocal')->once();

    // 2. Мокаем коллектор (теперь нужен и HardwareContext)
    $collector = Mockery::mock(MetricsCollector::class);
    $collector->shouldReceive('getMetrics')->andReturn(new NodeMetrics(
        90.0, 90.0, 500.0, 0.0, time(), 'test'
    ));
    $collector->shouldReceive('getHardwareContext')->andReturn(
        new HardwareContext(4, 8.0, 'Linux', '8.2')
    );

    // 3. Мокаем интеллект на 100% отсечение
    $intelligence = Mockery::mock(SwarmIntelligence::class);
    $intelligence->shouldReceive('computeSheddingRate')->andReturn(100.0);

    // 4. Собираем Middleware вручную
    $middleware = new AltruismMiddleware($repository, $collector, $intelligence);

    // 5. Проверяем исключение
    expect(fn() => $middleware->handle(Request::create('/'), fn() => response('ok')))
        ->toThrow(HiveOvercapacityException::class);
});


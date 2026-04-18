<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\Http\Middleware\AltruismMiddleware;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Illuminate\Http\Request;
use Mockery;

test('middleware sheds traffic when intelligence signals danger', function () {
    $repository = Mockery::mock(StateRepository::class);
    $repository->shouldReceive('updateLocal')->once();

    $collector = Mockery::mock(MetricsCollector::class);
    $collector->shouldReceive('getMetrics')->andReturn(new NodeMetrics(
        90.0, 90.0, 100.0, 0.0, time(), 'test'
    ));

    $intelligence = Mockery::mock(SwarmIntelligence::class);
    $intelligence->shouldReceive('computeSheddingRate')->andReturn(100.0);

    $middleware = new AltruismMiddleware($repository, $collector, $intelligence);

    try {
        $middleware->handle(Request::create('/'), fn() => response('ok'));
    } catch (HiveOvercapacityException $e) {
        $response = $e->render(Request::create('/'));
        expect($response->headers->get('Retry-After'))->toBe("60");
        return;
    }
    
    $this->fail('Exception not thrown');
});

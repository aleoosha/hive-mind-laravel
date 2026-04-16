<?php

use Aleoosha\HiveMind\Http\Middleware\AltruismMiddleware;
use Aleoosha\HiveMind\Contracts\StateRepository;
use Illuminate\Http\Request;

test('it blocks request when hive health is above threshold', function () {
    $mockRepo = Mockery::mock(StateRepository::class);
    $mockRepo->shouldReceive('getGlobalHealth')->andReturn(99);

    $middleware = new AltruismMiddleware($mockRepo);
    $request = Request::create('/test', 'GET');

    $response = $middleware->handle($request, function () {
        return response('OK');
    });

    expect($response->getStatusCode())->toBe(503);
});

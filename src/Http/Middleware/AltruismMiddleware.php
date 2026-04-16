<?php

namespace Aleoosha\HiveMind\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Aleoosha\HiveMind\Contracts\StateRepository;

class AltruismMiddleware
{
    public function __construct(protected StateRepository $repository) {}

    public function handle(Request $request, Closure $next)
    {
        if (!config('hive-mind.shedding.enabled')) {
            return $next($request);
        }

        $health = $this->repository->getGlobalHealth();

        if ($health >= config('hive-mind.shedding.activation_threshold')) {
            return response()->json([
                'error' => 'System overloaded',
                'retry_after' => 60
            ], 503);
        }

        return $next($request);
    }
}

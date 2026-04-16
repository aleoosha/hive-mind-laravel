<?php

namespace Aleoosha\HiveMind\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Aleoosha\HiveMind\Contracts\StateRepository;

class AltruismMiddleware
{
    public function __construct(
        protected StateRepository $repository
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // 1. Проверяем, включена ли защита в конфиге
        if (!config('hive-mind.shedding.enabled', true)) {
            return $next($request);
        }

        $health = $this->repository->getGlobalHealth();
        $threshold = config('hive-mind.shedding.activation_threshold', 75);

        if ($health >= $threshold) {
            \Log::warning("HiveMind: Load shedding triggered. Health: {$health}%");

            return response()->json([
                'status' => 'error',
                'message' => 'Service Temporarily Unavailable',
                'retry_after' => 60,
            ], 503);
        }

        return $next($request);
    }
}

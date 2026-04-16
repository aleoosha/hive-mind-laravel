<?php

namespace Aleoosha\HiveMind\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Aleoosha\HiveMind\Contracts\StateRepository;
use Illuminate\Support\Facades\Log;

class AltruismMiddleware
{
    public function __construct(
        protected StateRepository $repository
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $except = config('hive-mind.shedding.except', []);
        
        if ($request->is($except)) {
            return $next($request);
        }
        
        if (!config('hive-mind.shedding.enabled', true)) {
            return $next($request);
        }

        $health = $this->repository->getGlobalHealth();
        $threshold = config('hive-mind.shedding.activation_threshold', 75);

        if ($health >= $threshold) {
            if ($this->shouldShed($health, $threshold)) {
                Log::warning("HiveMind: Load shedding triggered. Mode: " . config('hive-mind.shedding.mode') . ". Health: {$health}%");

                return response()->json([
                    'status' => 'error',
                    'message' => 'Service Temporarily Unavailable',
                ], 503, [
                    'Retry-After' => config('hive-mind.shedding.retry_after', 60)
                ]);
            }
        }

        return $next($request);
    }

    /**
     * Логика принятия решения об отклонении запроса.
     */
    protected function shouldShed(int $health, int $threshold): bool
    {
        if ($health >= 100) {
            return true;
        }

        if (config('hive-mind.shedding.mode', 'static') === 'static') {
            return true;
        }

        $chanceOfRejection = (($health - $threshold) / (100 - $threshold)) * 100;

        return random_int(1, 100) <= $chanceOfRejection;
    }
}

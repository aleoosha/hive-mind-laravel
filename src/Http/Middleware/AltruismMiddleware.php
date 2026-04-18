<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Http\Middleware;

use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Closure;
use Illuminate\Http\Request;

final class AltruismMiddleware
{
    public function __construct(
        private readonly StateRepository $repository,
        private readonly MetricsCollector $collector,
        private readonly SwarmIntelligence $intelligence
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $metrics = $this->collector->getMetrics();
        $this->repository->updateLocal($metrics);

        $dropChance = $this->intelligence->computeSheddingRate($metrics);

        if ($this->shouldShed($dropChance)) {
            $this->terminateRequest($dropChance);
        }

        return $next($request);
    }

    private function shouldSkip(Request $request): bool
    {
        return !config('hive-mind.shedding.enabled', true) 
            || $request->is(config('hive-mind.shedding.except', []));
    }

    private function shouldShed(float $chance): bool
    {
        return $chance > 0 && random_int(1, 100) <= $chance;
    }

    private function terminateRequest(float $chance): void
    {
        throw new HiveOvercapacityException(
            message: "Swarm PID protection active ({$chance}%)",
            health: (int)$chance,
            retryAfter: (int)config('hive-mind.shedding.retry_after', 60)
        );
    }
}

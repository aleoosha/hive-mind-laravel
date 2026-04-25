<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Http\Middleware;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;
use Aleoosha\Telemetry\Contracts\StateRepositoryInterface;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware for automated load shedding based on DSS decisions.
 * Implements the "Altruism" principle: a node sacrifices its traffic
 * to ensure the survival of the entire cluster.
 */
final class AltruismMiddleware
{
    public function __construct(
        private readonly StateRepositoryInterface $stateRepository,
        private readonly PidStateRepositoryInterface $pidRepository,
        private readonly MetricsCollectorInterface $collector,
        private readonly DecisionEngine $engine
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // 1. Get current telemetry from this specific node
        $metrics = $this->collector->collect();

        // 2. Broadcast local metrics to the cluster state
        $this->stateRepository->updateLocal($metrics);

        // 3. Get the latest persistent PID state from the cluster
        $previousState = $this->pidRepository->getState('global_resilience');

        // 4. Ask the DSS Core for a survival decision
        $decision = $this->engine->evaluate($metrics, $previousState);

        // 5. Execute load shedding if the shedding rate is positive
        if ($this->shouldShed($decision->sheddingRate->toFloat())) {
            $this->terminateRequest($decision->sheddingRate->toFloat());
        }

        return $next($request);
    }

    /**
     * Check if the request should bypass protection.
     */
    private function shouldSkip(Request $request): bool
    {
        return ! config('hive-mind.shedding.enabled', true)
            || $request->is(config('hive-mind.shedding.except', []));
    }

    /**
     * Determine if the request should be dropped based on calculated probability.
     */
    private function shouldShed(float $chance): bool
    {
        return $chance > 0 && random_int(1, 100) <= ($chance * 100);
    }

    /**
     * Throw an exception to trigger a 503 response.
     */
    private function terminateRequest(float $chance): void
    {
        throw new HiveOvercapacityException(
            message: "Swarm DSS protection active (Shedding: {$chance}%)",
            health: (int) $chance,
            retryAfter: (int) config('hive-mind.shedding.retry_after', 60)
        );
    }
}

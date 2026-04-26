<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Http\Middleware;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;
use Aleoosha\Telemetry\Contracts\StateRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Middleware for automated load shedding based on DSS decisions.
 * Implements the "Fail-Safe" principle: protection should never break the application.
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
        try {
            if ($this->shouldSkip($request)) {
                return $next($request);
            }

            // 1. Collect telemetry
            $metrics = $this->collector->collect();

            // 2. Telemetry storage (might fail if Redis is down)
            $this->stateRepository->updateLocal($metrics);

            // 3. DSS Evaluation (might fail if repo or math logic errors)
            $decision = $this->engine->evaluate($metrics);

            // 4. Probability-based shedding
            if ($this->shouldShed($decision->sheddingRate->toFloat())) {
                $this->terminateRequest($decision->sheddingRate->toFloat());
            }
        } catch (HiveOvercapacityException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::critical('HiveMind Middleware Failure: ' . $e->getMessage(), [
                'exception' => $e,
                'url' => $request->fullUrl()
            ]);
        }

        return $next($request);
    }

    /**
     * Check if the request should bypass protection.
     */
    private function shouldSkip(Request $request): bool
    {
        return ! config('hive-mind.shedding.enabled', true) || $request->is(config('hive-mind.shedding.except', []));
    }

    /**
     * Determine if the request should be dropped based on calculated probability.
     */
    private function shouldShed(float $chance): bool
    {
        return $chance > 0 && random_int(1, 100) <= (int)($chance * 100);
    }

    /**
     * Throw an exception to trigger a 503 response.
     */
    private function terminateRequest(float $chance): void
    {
        throw new HiveOvercapacityException(
            message: "Swarm DSS protection active (Shedding: " . (int)($chance * 100) . "%)",
            health: (int) ($chance * 100),
            retryAfter: (int) config('hive-mind.shedding.retry_after', 60)
        );
    }
}

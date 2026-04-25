<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Traits;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;

/**
 * Trait AsHiveMember
 *
 * Provides self-protection for Eloquent models and external API call tracking.
 */
trait AsHiveMember
{
    /**
     * Automatic protection for Model saving.
     * Prevents DB write operations if the swarm is overloaded.
     *
     * @throws HiveOvercapacityException
     */
    public static function bootAsHiveMember(): void
    {
        static::saving(function () {
            /** @var MetricsCollectorInterface $collector */
            $collector = app(MetricsCollectorInterface::class);

            /** @var DecisionEngine $engine */
            $engine = app(DecisionEngine::class);

            /** @var PidStateRepositoryInterface $pidRepository */
            $pidRepository = app(PidStateRepositoryInterface::class);

            // 1. Get current telemetry
            $metrics = $collector->collect();

            // 2. Fetch the latest PID state from the cluster context
            $previousState = $pidRepository->getState('global_resilience');

            // 3. Evaluate health through the DSS Core
            $decision = $engine->evaluate($metrics, $previousState);
            
            // Rate is 0.0 to 1.0. We multiply by 100 to get percent (0-100)
            $ratePercent = $decision->sheddingRate->toFloat() * 100;

            // 4. Perform probabilistic load shedding
            if ($ratePercent > 0 && random_int(1, 100) <= $ratePercent) {
                throw new HiveOvercapacityException(
                    message: "Swarm DSS protection active (Shedding: {$ratePercent}%)",
                    health: (int) $ratePercent,
                    retryAfter: (int) config('hive-mind.shedding.retry_after', 60)
                );
            }
        });
    }

    /**
     * Wraps external calls (API, SDK) to record latency for PID analysis.
     */
    protected function hiveExternalCall(callable $callback): mixed
    {
        /** @var MetricsCollectorInterface $collector */
        $collector = app(MetricsCollectorInterface::class);
        $start = microtime(true);

        try {
            return $callback();
        } finally {
            $ms = (microtime(true) - $start) * 1000;
            // Record latency into the collector for future Decision cycles
            $collector->recordApiLatency($ms);
        }
    }
}

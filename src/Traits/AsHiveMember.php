<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Traits;

use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;

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
            $intelligence = app(SwarmIntelligence::class);
            $collector = app(MetricsCollector::class);

            $rate = $intelligence->computeSheddingRate($collector->getMetrics());

            if ($rate > 0 && random_int(1, 100) <= $rate) {
                throw new HiveOvercapacityException(
                    message: "Swarm PID protection active ({$rate}%)",
                    health: (int)$rate,
                    retryAfter: (int)config('hive-mind.shedding.retry_after', 60)
                );
            }
        });
    }

    /**
     * Wraps external calls (API, SDK) to record latency for PID analysis.
     */
    protected function hiveExternalCall(callable $callback): mixed
    {
        $collector = app(MetricsCollector::class);
        $start = microtime(true);

        try {
            return $callback();
        } finally {
            $ms = (microtime(true) - $start) * 1000;
            $collector->recordApiLatency($ms);
        }
    }
}

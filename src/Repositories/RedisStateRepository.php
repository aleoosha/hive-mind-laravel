<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;
use Aleoosha\Telemetry\Contracts\SerializerInterface;
use Aleoosha\Telemetry\Contracts\StateRepositoryInterface;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Redis implementation for cluster-wide telemetry state storage.
 */
class RedisStateRepository implements StateRepositoryInterface
{
    private const PREFIX = 'hive_node:';

    private ?FixedPoint $localHealthCache = null;

    public function __construct(
        protected SerializerInterface $serializer
    ) {}

    public function updateLocal(NodeMetrics $metrics): void
    {
        try {
            // Using app name and hostname to uniquely identify the node in the cluster
            $key = self::PREFIX.config('app.name').':'.gethostname();
            $data = $this->serializer->pack($metrics);

            Redis::setex(
                $key,
                (int) config('hive-mind.broadcast.ttl_seconds', 5),
                $data
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function getGlobalHealth(): FixedPoint
    {
        if ($this->localHealthCache !== null) {
            return $this->localHealthCache;
        }

        // Note: Real aggregation logic is moved to DSS Core.
        // This method returns the calculated health stored in Redis by the Pulse command.
        try {
            $health = Redis::get('hive_global_health');

            return $this->localHealthCache = new FixedPoint((int) ($health ?? 0));
        } catch (Throwable) {
            return new FixedPoint(0);
        }
    }

    public function flushLocalCache(): void
    {
        $this->localHealthCache = null;
    }
}

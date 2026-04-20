<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\Contracts\Serializer;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\Support\FixedPoint;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RedisStateRepository implements StateRepository
{
    private const PREFIX = 'hive_node:';
    private ?int $localCache = null;

    public function __construct(
        protected Serializer $serializer
    ) {}

    public function updateLocal(NodeMetrics $metrics): void
    {
        try {
            $key = self::PREFIX . config('app.name') . ':' . gethostname();
            $data = $this->serializer->pack($metrics->toArray());
            
            Redis::setex($key, (int)config('hive-mind.broadcast.ttl_seconds', 5), $data);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function getGlobalHealth(): int
    {
        if ($this->localCache !== null) {
            return $this->localCache;
        }

        try {
            $keys = Redis::keys(self::PREFIX . '*');
            if (empty($keys)) {
                return 0;
            }

            $scores = $this->calculateScores($keys);

            $avg = empty($scores) ? 0 : array_sum($scores) / count($scores);
            return $this->localCache = FixedPoint::fromFloat((float)$avg)->toInt();
        } catch (Throwable) {
            return 0;
        }
    }

    private function calculateScores(array $keys): array
    {
        $scores = [];
        $now = microtime(true);
        $thresholds = config('hive-mind.thresholds');
        $redisPrefix = (string)config('database.redis.options.prefix', '');

        foreach ($keys as $key) {
            $raw = Redis::get(str_replace($redisPrefix, '', $key));
            if (!$raw) continue;

            $data = $this->serializer->unpack($raw);
            if (($now - (float)($data['timestamp'] ?? 0)) > 10) continue;

            $scores[] = $this->computeStress($data, $thresholds);
        }

        return $scores;
    }

    private function computeStress(array $data, array $thresholds): float
    {
        $cpu = (($data['cpu'] ?? 0) / max($thresholds['cpu_percent'], 1)) * 100;
        $mem = (($data['memory'] ?? 0) / max($thresholds['memory_percent'], 1)) * 100;

        return min(100.0, max($cpu, $mem));
    }

    public function flushLocalCache(): void
    {
        $this->localCache = null;
    }
}

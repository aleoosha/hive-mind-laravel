<?php

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\Contracts\Serializer;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Illuminate\Support\Facades\Redis;

class RedisStateRepository implements StateRepository
{
    private const PREFIX = 'hive_node:';
    private ?int $localCache = null;

    public function __construct(
        protected Serializer $serializer
    ) {}

    public function updateLocal(NodeMetrics $metrics): void
    {
        $nodeId = config('app.name') . ':' . gethostname();
        $key = self::PREFIX . $nodeId;

        $data = $this->serializer->pack($metrics->toArray());

        Redis::setex($key, config('hive-mind.broadcast.ttl_seconds', 5), $data);
    }

    public function getGlobalHealth(): int
    {
        if ($this->localCache !== null) {
            return $this->localCache;
        }

        $keys = Redis::keys(self::PREFIX . '*');
        if (empty($keys)) return 0;

        $now = microtime(true);
        $thresholds = config('hive-mind.thresholds');
        $scores = [];

        foreach ($keys as $key) {
            $cleanKey = str_replace(config('database.redis.options.prefix', ''), '', $key);
            $raw = Redis::get($cleanKey);
            if (!$raw) continue;

            $data = $this->serializer->unpack($raw);

            if (($now - ($data['timestamp'] ?? 0)) > 10) {
                continue;
            }

            $cpuStress = (($data['cpu'] ?? 0) / $thresholds['cpu_percent']) * 100;
            $memStress = (($data['memory'] ?? 0) / $thresholds['memory_percent']) * 100;

            $scores[] = min(100, max($cpuStress, $memStress));
        }

        $count = count($scores);
        
        return $this->localCache = ($count > 0) ? (int)(array_sum($scores) / $count) : 0;
    }

    public function flushLocalCache(): void
    {
        $this->localCache = null;
    }
}

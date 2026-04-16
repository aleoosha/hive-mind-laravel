<?php

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\Contracts\Serializer;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Illuminate\Support\Facades\Redis;

class RedisStateRepository implements StateRepository
{
    private const PREFIX = 'hive_node:';

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
        $keys = Redis::keys(self::PREFIX . '*');
        if (empty($keys)) return 0;

        $totalCpu = 0;
        $count = 0;

        foreach ($keys as $key) {
            $cleanKey = str_replace(config('database.redis.options.prefix', ''), '', $key);
            $raw = Redis::get($cleanKey);
            
            if ($raw) {
                $decoded = $this->serializer->unpack($raw);
                $totalCpu += $decoded['cpu'] ?? 0;
                $count++;
            }
        }

        return $count > 0 ? (int)($totalCpu / $count) : 0;
    }
}

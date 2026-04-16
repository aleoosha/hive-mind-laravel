<?php

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Illuminate\Support\Facades\Redis;

class RedisStateRepository implements StateRepository
{
    private const KEY_PREFIX = 'hive_node:';

    public function updateLocal(array $metrics): void
    {
        $id = config('app.name') . '_' . gethostname();
        Redis::setex(self::KEY_PREFIX . $id, config('hive-mind.broadcast.ttl_seconds'), json_encode($metrics));
    }

    public function getGlobalHealth(): int
    {
        $keys = Redis::keys(self::KEY_PREFIX . '*');
        if (empty($keys)) return 0;

        $nodes = array_map(fn($key) => json_encode(Redis::get($key)), $keys);
        // Тут будет математика усреднения. Пока вернем заглушку.
        return 50; 
    }
}

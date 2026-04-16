<?php

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Illuminate\Support\Facades\Redis;

class RedisStateRepository implements StateRepository
{
    private const PREFIX = 'hive_node:';

    public function updateLocal(NodeMetrics $metrics): void
    {
        // Создаем уникальное имя пчелы: проект + имя хоста
        $nodeId = config('app.name') . ':' . gethostname();
        $key = self::PREFIX . $nodeId;

        // Пока JSON, бинарку добавим следующим шагом
        $data = json_encode($metrics->toArray());

        // TTL берем из конфига (по умолчанию 5 секунд)
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
            $data = Redis::get($cleanKey);
            
            if ($decoded = json_decode($data, true)) {
                $totalCpu += $decoded['cpu'];
                $count++;
            }
        }

        return $count > 0 ? (int)($totalCpu / $count) : 0;
    }
}

<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\DTO\PidResult;
use Illuminate\Support\Facades\Redis;

final class RedisPidStateRepository implements PidStateRepository
{
    private const PREFIX = 'hive_pid:';

    public function getState(string $channel): PidResult
    {
        $data = Redis::get(self::PREFIX . $channel);

        if (!$data) {
            // Возвращаем "нулевое" состояние, если данных еще нет
            return new PidResult(0.0, 0.0, 0.0, microtime(true));
        }

        $decoded = json_decode($data, true);

        return new PidResult(
            output: (float)($decoded['o'] ?? 0.0),
            lastError: (float)($decoded['e'] ?? 0.0),
            integral: (float)($decoded['i'] ?? 0.0),
            timestamp: (float)($decoded['t'] ?? microtime(true))
        );
    }

    public function saveState(string $channel, PidResult $result): void
    {
        $data = json_encode([
            'o' => $result->output,
            'e' => $result->lastError,
            'i' => $result->integral,
            't' => $result->timestamp,
        ]);

        // Храним состояние чуть дольше интервала опроса, чтобы оно не протухло
        Redis::setex(self::PREFIX . $channel, 60, $data);
    }
}

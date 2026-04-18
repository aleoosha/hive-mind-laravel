<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\Contracts\Serializer;
use Aleoosha\HiveMind\DTO\PidResult;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class RedisPidStateRepository implements PidStateRepository
{
    private const PREFIX = 'hive_pid:';

    public function __construct(
        private readonly Serializer $serializer
    ) {}

    public function getState(string $metric): PidResult
    {
        $raw = Redis::get(self::PREFIX . $metric);

        if (!$raw) {
            return new PidResult(0.0, 0.0, 0.0, microtime(true), 0.0, 0.0, 0.0);
        }

        try {
            $data = $this->serializer->unpack($raw);
            return new PidResult(
                (float)($data['output'] ?? 0.0),
                (float)($data['last_error'] ?? 0.0),
                (float)($data['integral'] ?? 0.0),
                (float)($data['timestamp'] ?? microtime(true)),
                (float)($data['kp'] ?? 0.0),
                (float)($data['ki'] ?? 0.0),
                (float)($data['kd'] ?? 0.0)
            );
        } catch (Throwable) {
            return new PidResult(0.0, 0.0, 0.0, microtime(true), 0.0, 0.0, 0.0);
        }
    }

    public function saveState(string $metric, PidResult $result): void
    {
        $data = $this->serializer->pack($result->toArray());
        // Храним состояние 24 часа, чтобы опыт не пропадал при перезагрузках
        Redis::setex(self::PREFIX . $metric, 86400, $data);
    }
}

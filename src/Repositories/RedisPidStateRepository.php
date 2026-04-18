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
    private const TTL = 86400;

    public function __construct(
        private readonly Serializer $serializer
    ) {}

    public function getState(string $metric): PidResult
    {
        $raw = Redis::get(self::PREFIX . $metric);

        if (!$raw) {
            return $this->emptyResult();
        }

        try {
            return $this->mapToDto($this->serializer->unpack($raw));
        } catch (Throwable) {
            return $this->emptyResult();
        }
    }

    public function saveState(string $metric, PidResult $result): void
    {
        $data = $this->serializer->pack($result->toArray());
        Redis::setex(self::PREFIX . $metric, self::TTL, $data);
    }

    private function mapToDto(array $data): PidResult
    {
        return new PidResult(
            (float)($data['output'] ?? 0.0),
            (float)($data['last_error'] ?? 0.0),
            (float)($data['integral'] ?? 0.0),
            (float)($data['timestamp'] ?? microtime(true)),
            (float)($data['kp'] ?? 0.0),
            (float)($data['ki'] ?? 0.0),
            (float)($data['kd'] ?? 0.0)
        );
    }

    private function emptyResult(): PidResult
    {
        return new PidResult(0.0, 0.0, 0.0, microtime(true), 0.0, 0.0, 0.0);
    }
}

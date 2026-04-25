<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\TauPid\Contracts\DTO\FixedPidResult;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\Telemetry\Contracts\SerializerInterface;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Redis adapter for persisting PID calculation states and adaptive settings.
 */
final class RedisPidStateRepository implements PidStateRepositoryInterface
{
    private const PREFIX = 'hive_pid:';

    private const TTL = 86400;

    public function __construct(
        private readonly SerializerInterface $serializer
    ) {}

    public function getState(string $key): ?FixedPidResult
    {
        $raw = Redis::get(self::PREFIX.$key);
        if (! $raw) {
            return null;
        }

        try {
            return $this->serializer->unpack($raw);
        } catch (Throwable) {
            return null;
        }
    }

    public function saveState(string $key, FixedPidResult $state): void
    {
        Redis::setex(
            self::PREFIX.$key,
            self::TTL,
            $this->serializer->pack($state)
        );
    }

    public function saveSettings(string $key, PidSettings $settings): void
    {
        Redis::setex(
            self::PREFIX.'settings:'.$key,
            self::TTL,
            $this->serializer->pack($settings)
        );
    }

    public function getSettings(string $key): ?PidSettings
    {
        $raw = Redis::get(self::PREFIX.'settings:'.$key);

        return $raw ? $this->serializer->unpack($raw) : null;
    }
}

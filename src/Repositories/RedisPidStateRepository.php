<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\Contracts\Serializer;
use Aleoosha\HiveMind\DTO\FixedPidResult;
use Aleoosha\HiveMind\Support\FixedPoint;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class RedisPidStateRepository implements PidStateRepository
{
    private const PREFIX = 'hive_pid:';
    private const TTL = 86400;

    public function __construct(
        private readonly Serializer $serializer
    ) {}

    public function getState(string $metric): FixedPidResult
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

    public function saveState(string $metric, FixedPidResult $result): void
    {
        $data = $this->serializer->pack([
            'output'     => $result->output->toInt(),
            'last_error' => $result->lastError->toInt(),
            'integral'   => $result->integral->toInt(),
            'timestamp'  => $result->timestamp,
            'kp'         => $result->kp->toInt(),
            'ki'         => $result->ki->toInt(),
            'kd'         => $result->kd->toInt(),
        ]);

        Redis::setex(self::PREFIX . $metric, self::TTL, $data);
    }

    private function mapToDto(array $data): FixedPidResult
    {
        return new FixedPidResult(
            output:    FixedPoint::raw((int)($data['output'] ?? 0)),
            lastError: FixedPoint::raw((int)($data['last_error'] ?? 0)),
            integral:  FixedPoint::raw((int)($data['integral'] ?? 0)),
            timestamp: (float)($data['timestamp'] ?? microtime(true)),
            kp:        FixedPoint::raw((int)($data['kp'] ?? 0)),
            ki:        FixedPoint::raw((int)($data['ki'] ?? 0)),
            kd:        FixedPoint::raw((int)($data['kd'] ?? 0))
        );
    }

    private function emptyResult(): FixedPidResult
    {
        $zero = FixedPoint::raw(0);
        return new FixedPidResult($zero, $zero, $zero, microtime(true), $zero, $zero, $zero);
    }
}

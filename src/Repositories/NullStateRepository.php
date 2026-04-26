<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;
use Aleoosha\Telemetry\Contracts\StateRepositoryInterface;

/**
 * Fallback repository used when Redis or other telemetry storage is offline.
 */
final class NullStateRepository implements StateRepositoryInterface
{
    public function updateLocal(NodeMetrics $metrics): void {}

    public function getGlobalHealth(): FixedPoint
    {
        return FixedPoint::fromInt(100);
    }

    public function flushLocalCache(): void {}
}

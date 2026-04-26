<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;
use Aleoosha\Telemetry\Contracts\StateRepositoryInterface;
use Laravel\Octane\Facades\Octane;

final class SwooleStateRepository implements StateRepositoryInterface
{
    private const TABLE_NAME = 'hive_telemetry';

    public function updateLocal(NodeMetrics $metrics): void
    {
        Octane::table(self::TABLE_NAME)->set($metrics->nodeId, [
            'cpu' => $metrics->cpu->value,
            'ram' => $metrics->memory->value,
            'updated_at' => time(),
        ]);
    }

    public function getGlobalHealth(): FixedPoint
    {
        $totalCpu = 0;
        $count = 0;

        foreach (Octane::table(self::TABLE_NAME) as $nodeId => $row) {
            if (time() - $row['updated_at'] > 10) {
                continue;
            }
            $totalCpu += $row['cpu'];
            $count++;
        }

        if ($count === 0) {
            return FixedPoint::fromInt(0);
        }

        return new FixedPoint((int) ($totalCpu / $count));
    }

    public function flushLocalCache(): void {}
}

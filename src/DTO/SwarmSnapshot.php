<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\DTO;

use Aleoosha\HiveMind\Support\FixedPoint;

final class SwarmSnapshot
{
    public function __construct(
        public readonly float $avgHealth,
        public readonly float $avgCpu,
        public readonly float $maxCpu,
        public readonly float $avgDbLatency,
        public readonly float $maxDbLatency,
        public readonly float $avgApiLatency,
        public readonly float $maxApiLatency,
        public readonly float $avgShedding,
        public readonly string $thresholdsSnapshot,
        public readonly int $sampleCount,
        public readonly int $nodeCount
    ) {}

    public function toArray(): array
    {
        return [
            'avg_health'          => FixedPoint::fromFloat($this->avgHealth)->toInt(),
            'avg_cpu'             => FixedPoint::fromFloat($this->avgCpu)->toInt(),
            'max_cpu'             => FixedPoint::fromFloat($this->maxCpu)->toInt(),
            'avg_db_latency'      => FixedPoint::fromFloat($this->avgDbLatency)->toInt(),
            'max_db_latency'      => FixedPoint::fromFloat($this->maxDbLatency)->toInt(),
            'avg_api_latency'     => FixedPoint::fromFloat($this->avgApiLatency)->toInt(),
            'max_api_latency'     => FixedPoint::fromFloat($this->maxApiLatency)->toInt(),
            'shedding_rate'       => FixedPoint::fromFloat($this->avgShedding)->toInt(),
            'thresholds_snapshot' => $this->thresholdsSnapshot,
            'sample_count'        => $this->sampleCount,
            'node_count'          => $this->nodeCount,
        ];
    }
}

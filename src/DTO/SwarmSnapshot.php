<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\DTO;

use Aleoosha\Support\Types\FixedPoint;

/**
 * Snapshot of the entire swarm state for a specific period.
 * Used for database archiving.
 */
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

    /**
     * Convert the snapshot to an array for database insertion.
     * Uses the new FixedPoint library for precise integer conversion.
     */
    public function toArray(): array
    {
        return [
            'avg_health' => FixedPoint::fromFloat($this->avgHealth)->value,
            'avg_cpu' => FixedPoint::fromFloat($this->avgCpu)->value,
            'max_cpu' => FixedPoint::fromFloat($this->maxCpu)->value,
            'avg_db_latency' => FixedPoint::fromFloat($this->avgDbLatency)->value,
            'max_db_latency' => FixedPoint::fromFloat($this->maxDbLatency)->value,
            'avg_api_latency' => FixedPoint::fromFloat($this->avgApiLatency)->value,
            'max_api_latency' => FixedPoint::fromFloat($this->maxApiLatency)->value,
            'avg_shedding' => FixedPoint::fromFloat($this->avgShedding)->value,
            'thresholds_snapshot' => $this->thresholdsSnapshot,
            'sample_count' => $this->sampleCount,
            'node_count' => $this->nodeCount,
        ];
    }
}

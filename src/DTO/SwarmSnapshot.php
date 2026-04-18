<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\DTO;

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
            'avg_health'          => $this->avgHealth,
            'avg_cpu'             => $this->avgCpu,
            'max_cpu'             => $this->maxCpu,
            'avg_db_latency'      => $this->avgDbLatency,
            'max_db_latency'      => $this->maxDbLatency,
            'avg_api_latency'     => $this->avgApiLatency,
            'max_api_latency'     => $this->maxApiLatency,
            'shedding_rate'       => $this->avgShedding,
            'thresholds_snapshot' => $this->thresholdsSnapshot,
            'sample_count'        => $this->sampleCount,
            'node_count'          => $this->nodeCount,
        ];
    }
}

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
}

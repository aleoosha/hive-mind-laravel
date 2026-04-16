<?php

namespace Aleoosha\HiveMind\Dto;

class NodeMetrics
{
    public function __construct(
        public readonly float $cpu,
        public readonly float $memory,
        public readonly float $dbLatencyMs = 0.0,
        public readonly float $timestamp = 0.0
    ) {
    }

    public function toArray(): array
    {
        return [
            'cpu' => $this->cpu,
            'memory' => $this->memory,
            'db_latency' => $this->dbLatencyMs,
            'timestamp' => $this->timestamp ?: microtime(true),
        ];
    }
}

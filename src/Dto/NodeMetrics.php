<?php

namespace Aleoosha\HiveMind\Dto;

readonly class NodeMetrics
{
    public function __construct(
        public float $cpu,
        public float $memory,
        public float $dbLatencyMs = 0.0,
        public float $timestamp = 0.0
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

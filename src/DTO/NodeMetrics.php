<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\DTO;

/**
 * Иммутабельный объект метрик узла.
 */
final class NodeMetrics
{
    public function __construct(
        public readonly float $cpu,
        public readonly float $memory,
        public readonly float $dbLatency,
        public readonly float $apiLatency,
        public readonly int $timestamp,
        public readonly string $nodeId
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cpu: (float)($data['cpu'] ?? 0.0),
            memory: (float)($data['memory'] ?? 0.0),
            dbLatency: (float)($data['db_latency'] ?? 0.0),
            apiLatency: (float)($data['api_latency'] ?? 0.0),
            timestamp: (int)($data['timestamp'] ?? time()),
            nodeId: (string)($data['node_id'] ?? 'unknown')
        );
    }

    public function toArray(): array
    {
        return [
            'cpu' => $this->cpu,
            'memory' => $this->memory,
            'db_latency' => $this->dbLatency,
            'api_latency' => $this->apiLatency,
            'timestamp' => $this->timestamp,
            'node_id' => $this->nodeId,
        ];
    }
}

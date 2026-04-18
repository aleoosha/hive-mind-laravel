<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\DTO\AccumulatorState;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\DTO\SwarmSnapshot;

final class MetricsAccumulator
{
    public function __construct(
        private readonly AccumulatorState $state
    ) {}

    public function push(int $health, NodeMetrics $metrics, float $sheddingRate): void
    {
        $this->state->count++;
        
        $this->state->sumHealth += $health;
        $this->state->sumShedding += $sheddingRate; 
        
        $this->state->sumCpu += $metrics->cpu;
        $this->state->maxCpu = max($this->state->maxCpu, $metrics->cpu);
        
        $this->state->sumDb += $metrics->dbLatency;
        $this->state->maxDb = max($this->state->maxDb, $metrics->dbLatency);
        
        $this->state->sumApi += $metrics->apiLatency;
        $this->state->maxApi = max($this->state->maxApi, $metrics->apiLatency);
    }

    public function flush(int $activeNodes): SwarmSnapshot
    {
        $count = max($this->state->count, 1);

        $snapshot = new SwarmSnapshot(
            avgHealth: $this->state->sumHealth / $count,
            avgCpu: $this->state->sumCpu / $count,
            maxCpu: $this->state->maxCpu,
            avgDbLatency: $this->state->sumDb / $count,
            maxDbLatency: $this->state->maxDb,
            avgApiLatency: $this->state->sumApi / $count,
            maxApiLatency: $this->state->maxApi,
            avgShedding: $this->state->sumShedding / $count,
            thresholdsSnapshot: json_encode(config('hive-mind.thresholds')),
            sampleCount: $this->state->count,
            nodeCount: $activeNodes
        );

        $this->state->reset();

        return $snapshot;
    }
}

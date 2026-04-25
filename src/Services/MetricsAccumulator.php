<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\DssCore\DTO\DecisionResult;
use Aleoosha\HiveMind\DTO\AccumulatorState;
use Aleoosha\HiveMind\DTO\SwarmSnapshot;
use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;

/**
 * Accumulates real-time metrics and decisions into minute-long snapshots.
 */
final class MetricsAccumulator
{
    public function __construct(
        private readonly AccumulatorState $state
    ) {}

    /**
     * Push current telemetry and DSS decision into the accumulator.
     */
    public function push(NodeMetrics $metrics, DecisionResult $decision): void
    {
        $this->state->count++;

        // We take the load level (health) and shedding rate from the Decision object
        $this->state->sumHealth += $decision->systemLoad->value;
        $this->state->sumShedding += $decision->sheddingRate->value;

        // Update technical metrics using FixedPoint values
        $this->updateMetric('Cpu', $metrics->cpu->value);
        $this->updateMetric('Db', $metrics->dbLatency->value);
        $this->updateMetric('Api', $metrics->apiLatency->value);
    }

    public function flush(int $activeNodes): SwarmSnapshot
    {
        $count = max($this->state->count, 1);

        // Calculate averages and create a final database-ready snapshot
        $snapshot = new SwarmSnapshot(
            avgHealth: (float) ($this->state->sumHealth / $count / 1000),
            avgCpu: (float) ($this->state->sumCpu / $count / 1000),
            maxCpu: (float) ($this->state->maxCpu / 1000),
            avgDbLatency: (float) ($this->state->sumDb / $count / 1000),
            maxDbLatency: (float) ($this->state->maxDb / 1000),
            avgApiLatency: (float) ($this->state->sumApi / $count / 1000),
            maxApiLatency: (float) ($this->state->maxApi / 1000),
            avgShedding: (float) ($this->state->sumShedding / $count / 1000),
            thresholdsSnapshot: json_encode(config('hive-mind.thresholds')),
            sampleCount: $this->state->count,
            nodeCount: $activeNodes
        );

        $this->state->reset();

        return $snapshot;
    }

    private function updateMetric(string $name, int $value): void
    {
        $sumKey = "sum{$name}";
        $maxKey = "max{$name}";

        $this->state->{$sumKey} += $value;
        $this->state->{$maxKey} = max($this->state->{$maxKey}, $value);
    }
}

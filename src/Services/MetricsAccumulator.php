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

        $this->updateMetric('Cpu', $metrics->cpu);
        $this->updateMetric('Db', $metrics->dbLatency);
        $this->updateMetric('Api', $metrics->apiLatency);
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

    private function updateMetric(string $name, float $value): void
    {
        $sumKey = "sum{$name}";
        $maxKey = "max{$name}";

        $this->state->{$sumKey} += $value;
        $this->state->{$maxKey} = max($this->state->{$maxKey}, $value);
    }
}

<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\DTO\PidSettings;
use Aleoosha\HiveMind\DTO\PidResult;
use Aleoosha\HiveMind\DTO\HardwareContext;

class SwarmIntelligence
{
    private const PROFILES = [
        'cpu_percent'    => ['kp' => 0.6, 'ki' => 0.1, 'kd' => 0.4, 'anti_windup' => 20.0],
        'db_latency_ms'  => ['kp' => 0.5, 'ki' => 0.3, 'kd' => 0.2, 'anti_windup' => 30.0],
        'api_latency_ms' => ['kp' => 0.5, 'ki' => 0.2, 'kd' => 0.2, 'anti_windup' => 20.0],
        'memory_percent' => ['kp' => 1.0, 'ki' => 0.0, 'kd' => 0.1, 'anti_windup' => 0.0],
    ];

    public function __construct(
        private readonly PidCalculator $calculator,
        private readonly PidStateRepository $stateRepository,
        private readonly PidTuner $tuner,
        private readonly MetricsCollector $collector
    ) {}

    public function computeSheddingRate(NodeMetrics $metrics): float
    {
        $thresholds = config('hive-mind.thresholds', []);
        $hardware = $this->collector->getHardwareContext();
        $signals = [0.0];

        foreach (self::PROFILES as $metric => $params) {
            if (isset($thresholds[$metric])) {
                $signals[] = $this->processMetric($metric, $params, $metrics, (float)$thresholds[$metric], $hardware);
            }
        }

        return (float)max($signals);
    }

    private function processMetric(string $metric, array $params, NodeMetrics $metrics, float $target, HardwareContext $hw): float
    {
        $state = $this->stateRepository->getState($metric);
        $currentValue = $this->extractValue($metrics, $metric);
        $error = ($currentValue - $target) / max($target, 0.0001);

        $settings = $this->prepareSettings($params, $hw, $state, $error);
        
        $result = $this->calculator->calculate(
            $settings, $target, $currentValue, $state->lastError, $state->integral, $state->timestamp
        );

        $this->persistState($metric, $result, $settings);

        return $result->output;
    }

    private function prepareSettings(array $params, HardwareContext $hw, PidResult $state, float $error): PidSettings
    {
        $baseKp = $params['kp'] / (1 + log10($hw->cpuCores));
        $base = new PidSettings($baseKp, $params['ki'], $params['kd'], $params['anti_windup']);

        return $this->tuner->tune($base, $state, $error);
    }

    private function persistState(string $metric, PidResult $res, PidSettings $set): void
    {
        $this->stateRepository->saveState($metric, new PidResult(
            $res->output, $res->lastError, $res->integral, $res->timestamp, $set->kp, $set->ki, $set->kd
        ));
    }

    private function extractValue(NodeMetrics $metrics, string $key): float
    {
        return match ($key) {
            'cpu_percent'    => $metrics->cpu,
            'memory_percent' => $metrics->memory,
            'db_latency_ms'  => $metrics->dbLatency,
            'api_latency_ms' => $metrics->apiLatency,
            default          => 0.0,
        };
    }
}

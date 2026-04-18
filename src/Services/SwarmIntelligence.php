<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\DTO\PidSettings;

class SwarmIntelligence
{
    private const PROFILES = [
        'cpu_percent'    => ['kp' => 0.6, 'ki' => 0.1, 'kd' => 0.4, 'anti_windup' => 15.0], 
        'db_latency_ms'  => ['kp' => 0.5, 'ki' => 0.3, 'kd' => 0.2, 'anti_windup' => 30.0],
        'api_latency_ms' => ['kp' => 0.5, 'ki' => 0.2, 'kd' => 0.2, 'anti_windup' => 20.0],
        'memory_percent' => ['kp' => 1.0, 'ki' => 0.0, 'kd' => 0.1, 'anti_windup' => 0.0],
    ];

    public function __construct(
        private readonly PidCalculator $calculator,
        private readonly PidStateRepository $stateRepository
    ) {}

    /**
     * Вычисляет глобальный коэффициент отсечения трафика (0..100).
     */
    public function computeSheddingRate(NodeMetrics $metrics): float
    {
        $thresholds = config('hive-mind.thresholds', []);
        $signals = [0.0];

        foreach (self::PROFILES as $metric => $params) {
            if (!isset($thresholds[$metric])) {
                continue;
            }

            // 1. Получаем текущее состояние этого канала
            $state = $this->stateRepository->getState($metric);

            // 2. Делаем расчет
            $result = $this->calculator->calculate(
                settings: new PidSettings(
                    $params['kp'], 
                    $params['ki'], 
                    $params['kd'], 
                    $params['anti_windup']
                ),
                target: (float)$thresholds[$metric],
                current: $this->extractValue($metrics, $metric),
                lastError: $state->lastError,
                integral: $state->integral,
                lastTime: $state->timestamp
            );

            // 3. Сохраняем "опыт" для следующего запроса
            $this->stateRepository->saveState($metric, $result);

            $signals[] = $result->output;
        }

        // ТРИЗ: Селектор максимума — реагируем на самое слабое звено
        return (float)max($signals);
    }

    private function extractValue(NodeMetrics $metrics, string $key): float
    {
        return match ($key) {
            'cpu_percent' => $metrics->cpu,
            'memory_percent' => $metrics->memory,
            'db_latency_ms' => $metrics->dbLatency,
            'api_latency_ms' => $metrics->apiLatency,
            default => 0.0,
        };
    }
}

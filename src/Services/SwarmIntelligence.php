<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\DTO\PidSettings;
use Aleoosha\HiveMind\DTO\PidResult;

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
        $signals = [0.0];
        
        // 1. Контекст железа для адаптации базы
        $hardware = $this->collector->getHardwareContext();

        foreach (self::PROFILES as $metric => $params) {
            if (!isset($thresholds[$metric])) continue;

            // 2. Получаем состояние из Redis (там уже лежат $state->kp и $state->ki)
            $state = $this->stateRepository->getState($metric);
            
            $target = (float)$thresholds[$metric];
            $currentValue = $this->extractValue($metrics, $metric);
            $currentError = ($currentValue - $target) / max($target, 0.0001);

            // 3. Рассчитываем базовый эталон под железо
            $baseKp = $params['kp'] / (1 + log10($hardware->cpuCores));
            $baseSettings = new PidSettings(
                $baseKp, 
                $params['ki'], 
                $params['kd'], 
                $params['anti_windup']
            );

            // 4. Тюнер берет базу и текущий опыт из $state
            // Если в Redis kp еще 0 (первый запуск), тюнер внутри сам подхватит базу
            $activeSettings = $this->tuner->tune($baseSettings, $state, $currentError);

            // 5. Расчет ПИД с тем, что выдал тюнер
            $result = $this->calculator->calculate(
                $activeSettings,
                $target,
                $currentValue,
                $state->lastError,
                $state->integral,
                $state->timestamp
            );

            // 6. Сохраняем в Redis обновленный результат с коэффициентами
            $this->stateRepository->saveState($metric, new PidResult(
                output: $result->output,
                lastError: $result->lastError,
                integral: $result->integral,
                timestamp: $result->timestamp,
                kp: $activeSettings->kp,
                ki: $activeSettings->ki,
                kd: $activeSettings->kd
            ));

            $signals[] = $result->output;
        }


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

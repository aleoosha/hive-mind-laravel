<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\DTO\PidResult;
use Aleoosha\HiveMind\DTO\PidSettings;

final class PidTuner
{
    /**
     * Адаптирует настройки ПИД на основе анализа поведения.
     */
    public function tune(PidSettings $base, PidResult $lastResult, float $currentError): PidSettings
    {
        // Берем текущие рабочие коэффициенты (из Redis или базу)
        $workingKp = ($lastResult->kp > 0) ? $lastResult->kp : $base->kp;
        $workingKi = ($lastResult->ki > 0) ? $lastResult->ki : $base->ki;

        $newKp = $workingKp;
        $newKi = $workingKi;

        // 1. Детектор автоколебаний (Резонанс)
        if (($currentError > 0 && $lastResult->lastError < 0) || 
            ($currentError < 0 && $lastResult->lastError > 0)) {
            $newKp = $workingKp * 0.90; 
        }

        // 2. Детектор застоя (Усиливаем интеграл)
        // Если ошибка существенна и стабильна
        if (abs($currentError) > 0.1 && abs($currentError - $lastResult->lastError) < 0.05) {
            $newKi = $workingKi + 0.05;
        }

        return new PidSettings(
            kp: max($base->kp * 0.1, min($base->kp * 2.0, $newKp)),
            ki: max(0.0, min(1.0, $newKi)),
            kd: $base->kd,
            antiWindup: $base->antiWindup
        );
    }
}

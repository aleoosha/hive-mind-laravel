<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\DTO\PidSettings;
use Aleoosha\HiveMind\DTO\PidResult;

/**
 * Чистый сервис для расчета ПИД-воздействия.
 */
final class PidCalculator
{
    /**
     * Вычисляет новое состояние и выходной сигнал регулятора.
     * 
     * @param PidSettings $settings Константы регулятора
     * @param float $target Целевое значение (уставка)
     * @param float $current Текущее значение метрики
     * @param float $lastError Ошибка из предыдущего шага
     * @param float $integral Накопленный интеграл из предыдущего шага
     * @param float|null $lastTime Timestamp последнего расчета
     */
    public function calculate(
        PidSettings $settings,
        float $target,
        float $current,
        float $lastError,
        float $integral,
        ?float $lastTime
    ): PidResult {
        $now = microtime(true);
        $dt = $lastTime ? ($now - $lastTime) : 0.0;

        // 1. Нормализация ошибки (уходим от абсолютных значений к относительным)
        // Если current = 110, а target = 100, ошибка будет 0.1 (10%)
        $error = ($current - $target) / max($target, 0.0001);

        // 2. P-звено: Пропорциональная реакция на текущее отклонение
        $pTerm = $settings->kp * $error;

        // 3. I-звено: Накопленная ошибка (интеграл)
        // Учитываем время dt, чтобы частота запросов не влияла на накопление
        $newIntegral = $integral;
        if ($dt > 0) {
            $newIntegral += $error * $dt;
            // Anti-Windup: ограничиваем интеграл, чтобы система не "зависла" в режиме отсечения
            $newIntegral = max(-$settings->antiWindup, min($settings->antiWindup, $newIntegral));
        }
        $iTerm = $settings->ki * $newIntegral;

        // 4. D-звено: Реакция на скорость изменения (дифференциал)
        $dTerm = ($dt > 0) ? ($settings->kd * ($error - $lastError) / $dt) : 0.0;

        // 5. Итоговый сигнал (Output)
        // Переводим из долей в проценты (0..100) для вероятностного отсечения
        $output = ($pTerm + $iTerm + $dTerm) * 100;

        return new PidResult(
            output: max(0.0, min(100.0, $output)),
            lastError: $error,
            integral: $newIntegral,
            timestamp: $now
        );
    }
}

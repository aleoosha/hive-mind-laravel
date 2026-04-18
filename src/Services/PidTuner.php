<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\DTO\PidResult;
use Aleoosha\HiveMind\DTO\PidSettings;

final class PidTuner
{
    public function tune(PidSettings $base, PidResult $lastResult, float $currentError): PidSettings
    {
        $kp = $lastResult->kp > 0 ? $lastResult->kp : $base->kp;
        $ki = $lastResult->ki > 0 ? $lastResult->ki : $base->ki;

        $kp = $this->detectResonance($currentError, $lastResult->lastError, $kp);
        $ki = $this->detectStagnation($currentError, $lastResult->lastError, $ki);

        return new PidSettings(
            kp: max($base->kp * 0.1, min($base->kp * 2.0, $kp)),
            ki: max(0.0, min(1.0, $ki)),
            kd: $base->kd,
            antiWindup: $base->antiWindup
        );
    }

    private function detectResonance(float $current, float $last, float $kp): float
    {
        if (($current > 0 && $last < 0) || ($current < 0 && $last > 0)) {
            return $kp * 0.90;
        }

        return $kp;
    }

    private function detectStagnation(float $current, float $last, float $ki): float
    {
        if (abs($current) > 0.1 && abs($current - $last) < 0.05) {
            return $ki + 0.05;
        }

        return $ki;
    }
}

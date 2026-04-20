<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\DTO\FixedPidResult;
use Aleoosha\HiveMind\DTO\PidSettings;

final class PidTuner
{
    public function tune(PidSettings $base, FixedPidResult $lastResult, float $currentError): PidSettings
    {
        $kp = $lastResult->kp->toInt() > 0 ? $lastResult->kp->toFloat() : $base->kp;
        $ki = $lastResult->ki->toInt() > 0 ? $lastResult->ki->toFloat() : $base->ki;

        $lastError = $lastResult->lastError->toFloat();
        
        $kp = $this->detectResonance($currentError, $lastError, $kp);
        $ki = $this->detectStagnation($currentError, $lastError, $ki);

        return new PidSettings(
            kp: round(max($base->kp * 0.1, min($base->kp * 2.0, $kp)), 4),
            ki: round(max(0.0, min(1.0, $ki)), 4),
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

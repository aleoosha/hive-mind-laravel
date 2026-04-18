<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\DTO\PidSettings;
use Aleoosha\HiveMind\DTO\PidResult;

final class PidCalculator
{
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
        $error = ($current - $target) / max($target, 0.0001);

        $newIntegral = $this->calculateIntegral($integral, $error, $dt, $settings->antiWindup);
        
        $output = $this->computeOutput($settings, $error, $lastError, $newIntegral, $dt);

        return new PidResult(
            output: max(0.0, min(100.0, $output)),
            lastError: $error,
            integral: $newIntegral,
            timestamp: $now,
            kp: $settings->kp,
            ki: $settings->ki,
            kd: $settings->kd
        );
    }

    private function calculateIntegral(float $integral, float $error, float $dt, float $limit): float
    {
        if ($dt <= 0) {
            return $integral;
        }

        return max(0.0, min($limit, $integral + ($error * $dt)));
    }

    private function computeOutput(PidSettings $s, float $err, float $lErr, float $integ, float $dt): float
    {
        $p = $s->kp * $err;
        $i = $s->ki * $integ;
        $d = ($dt > 0) ? ($s->kd * ($err - $lErr) / $dt) : 0.0;

        return ($p + $i + $d) * 100;
    }
}

<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\DTO\FixedPidResult;
use Aleoosha\HiveMind\DTO\PidSettings;
use Aleoosha\HiveMind\Support\FixedPoint;

final class PidCalculator
{
    public function calculate(
        PidSettings $settings,
        float $target,
        float $current,
        float $lastError,
        float $integral,
        ?float $lastTime
    ): FixedPidResult {
        $now = microtime(true);
        $dt = $lastTime ? ($now - $lastTime) : 0.0;

        $fTarget = FixedPoint::fromFloat($target);
        $fCurrent = FixedPoint::fromFloat($current);
        $fLastError = FixedPoint::fromFloat($lastError);
        $fIntegral = FixedPoint::fromFloat($integral);

        $fKp = FixedPoint::fromFloat($settings->kp);
        $fKi = FixedPoint::fromFloat($settings->ki);
        $fKd = FixedPoint::fromFloat($settings->kd);

        $error = $fCurrent->subtract($fTarget)->divide($fTarget);

        $newIntegral = $this->calculateIntegral($fIntegral, $error, $dt, $settings->antiWindup);

        $output = $this->computeOutput($fKp, $fKi, $fKd, $error, $fLastError, $newIntegral, $dt);

        return new FixedPidResult(
            output: $this->clamp($output, 0, 100),
            lastError: $error,
            integral: $newIntegral,
            timestamp: $now,
            kp: $fKp,
            ki: $fKi,
            kd: $fKd
        );
    }

    private function calculateIntegral(FixedPoint $integral, FixedPoint $error, float $dt, float $limit): FixedPoint
    {
        if ($dt <= 0) {
            return $integral;
        }

        $fDt = FixedPoint::fromFloat($dt);
        $fLimit = FixedPoint::fromFloat($limit);
        
        $newIntegral = $integral->add($error->multiply($fDt));

        return $this->clamp($newIntegral, 0, $limit);
    }

    private function computeOutput(
        FixedPoint $kp, 
        FixedPoint $ki, 
        FixedPoint $kd, 
        FixedPoint $err, 
        FixedPoint $lErr, 
        FixedPoint $integ, 
        float $dt
    ): FixedPoint {
        $pTerm = $kp->multiply($err);
        $iTerm = $ki->multiply($integ);
        
        $dTerm = FixedPoint::raw(0);
        if ($dt > 0) {
            $fDt = FixedPoint::fromFloat($dt);
            $dTerm = $err->subtract($lErr)->divide($fDt)->multiply($kd);
        }

        return $pTerm->add($iTerm)->add($dTerm)->multiply(FixedPoint::fromFloat(100.0));
    }

    private function clamp(FixedPoint $val, float $min, float $max): FixedPoint
    {
        $f = $val->toFloat();
        if ($f < $min) return FixedPoint::fromFloat($min);
        if ($f > $max) return FixedPoint::fromFloat($max);
        return $val;
    }
}

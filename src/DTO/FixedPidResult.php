<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\DTO;

use Aleoosha\HiveMind\Support\FixedPoint;

/**
 * Результат расчета и новое состояние регулятора.
 */
final class FixedPidResult
{
    public function __construct(
        public readonly FixedPoint $output,
        public readonly FixedPoint $lastError,
        public readonly FixedPoint $integral,
        public readonly float $timestamp,
        public readonly FixedPoint $kp,
        public readonly FixedPoint $ki,
        public readonly FixedPoint $kd
    ) {}

    public function toArray(): array
    {
        return [
            'output' => $this->output,
            'last_error' => $this->lastError,
            'integral' => $this->integral,
            'timestamp' => $this->timestamp,
            'kp' => $this->kp,
            'ki' => $this->ki,
            'kd' => $this->kd,
        ];
    }
}

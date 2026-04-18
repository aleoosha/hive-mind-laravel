<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\DTO;

/**
 * Результат расчета и новое состояние регулятора.
 */
final  class PidResult
{
    public function __construct(
        public readonly float $output,
        public readonly float $integral,
        public readonly float $lastError,
        public readonly float $timestamp,
        public float $kp,
        public float $ki,
        public float $kd
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

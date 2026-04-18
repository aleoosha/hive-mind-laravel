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
        public readonly float $timestamp
    ) {}
}

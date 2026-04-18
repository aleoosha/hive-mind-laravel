<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\DTO;

/**
 * Настройки конкретного канала ПИД.
 */
final  class PidSettings
{
    public function __construct(
        public readonly float $kp,
        public readonly float $ki,
        public readonly float $kd,
        public readonly float $antiWindup = 100.0
    ) {}
}

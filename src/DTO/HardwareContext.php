<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\DTO;

final class HardwareContext
{
    public function __construct(
        public readonly int $cpuCores,
        public readonly float $ramTotalGb,
        public readonly string $os,
        public readonly string $phpVersion
    ) {}
}

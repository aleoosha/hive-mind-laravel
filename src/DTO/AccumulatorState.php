<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\DTO;

/**
 * Внутренний объект для накопления итогов без хранения истории всех точек.
 */
final class AccumulatorState
{
    public float $sumHealth = 0.0;
    public float $sumCpu = 0.0;
    public float $maxCpu = 0.0;
    public float $sumDb = 0.0;
    public float $maxDb = 0.0;
    public float $sumApi = 0.0;
    public float $maxApi = 0.0;
    public float $sumShedding = 0.0;
    public int $count = 0;

    public function reset(): void
    {
        $this->sumHealth = 0.0;
        $this->sumCpu = 0.0;
        $this->maxCpu = 0.0;
        $this->sumDb = 0.0;
        $this->maxDb = 0.0;
        $this->sumApi = 0.0;
        $this->maxApi = 0.0;
        $this->sumShedding = 0.0;
        $this->count = 0;
    }
}

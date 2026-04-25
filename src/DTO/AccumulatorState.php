<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\DTO;

/**
 * Internal state for aggregating metrics before archiving.
 * Stores values as integers (FixedPoint raw values) to maintain precision.
 */
final class AccumulatorState
{
    public int $sumHealth = 0;
    public int $sumCpu = 0;
    public int $maxCpu = 0;
    public int $sumDb = 0;
    public int $maxDb = 0;
    public int $sumApi = 0;
    public int $maxApi = 0;
    public int $sumShedding = 0;
    public int $count = 0;

    /**
     * Reset all counters to zero for the next accumulation window.
     */
    public function reset(): void
    {
        $this->sumHealth = 0;
        $this->sumCpu = 0;
        $this->maxCpu = 0;
        $this->sumDb = 0;
        $this->maxDb = 0;
        $this->sumApi = 0;
        $this->maxApi = 0;
        $this->sumShedding = 0;
        $this->count = 0;
    }
}

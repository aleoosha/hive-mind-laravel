<?php

namespace Aleoosha\HiveMind\Contracts;

use Aleoosha\HiveMind\DTO\NodeMetrics;

interface StateRepository
{
    public function updateLocal(NodeMetrics $metrics): void;
    public function getGlobalHealth(): int;
}

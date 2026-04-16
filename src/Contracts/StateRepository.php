<?php

namespace Aleoosha\HiveMind\Contracts;

interface StateRepository
{
    public function updateLocal(array $metrics): void;
    public function getGlobalHealth(): int;
}

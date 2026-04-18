<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Contracts;

use Aleoosha\HiveMind\DTO\PidResult;

interface PidStateRepository
{
    public function getState(string $channel): PidResult;
    public function saveState(string $channel, PidResult $result): void;
}

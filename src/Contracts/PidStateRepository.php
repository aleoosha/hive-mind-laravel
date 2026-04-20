<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Contracts;

use Aleoosha\HiveMind\DTO\FixedPidResult;

interface PidStateRepository
{
    /**
     * Получить последнее состояние ПИД-регулятора для конкретной метрики.
     */
    public function getState(string $channel): FixedPidResult;

    /**
     * Сохранить обновленное состояние (включая накопленный интеграл и коэффициенты).
     */
    public function saveState(string $channel, FixedPidResult $result): void;
}

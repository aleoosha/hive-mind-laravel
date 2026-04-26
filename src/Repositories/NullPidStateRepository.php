<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Repositories;

use Aleoosha\TauPid\Contracts\DTO\FixedPidResult;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;

/**
 * Safe fallback for PID storage.
 */
final class NullPidStateRepository implements PidStateRepositoryInterface
{
    public function saveState(string $key, FixedPidResult $state): void {}

    public function getState(string $key): ?FixedPidResult
    {
        return null;
    }

    public function saveSettings(string $key, PidSettings $settings): void {}

    public function getSettings(string $key): ?PidSettings
    {
        return null;
    }
}

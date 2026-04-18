<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Traits;

use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Illuminate\Support\Facades\App;

trait AsHiveMember
{
    /**
     * Автоматическая защита при сохранении модели.
     */
    public static function bootAsHiveMember(): void
    {
        if (method_exists(static::class, 'saving')) {
            static::saving(function () {
                (new static)->guardAgainstHighLoad();
            });
        }
    }

    /**
     * Обертка для замера внешних API.
     */
    protected function hiveExternalCall(callable $callback): mixed
    {
        $collector = App::make(MetricsCollector::class);
        $start = microtime(true);
        
        try {
            return $callback();
        } finally {
            $duration = (microtime(true) - $start) * 1000;
            $collector->recordApiLatency($duration);
        }
    }

    /**
     * Проверка на "стресс" Роя на основе ПИД-регулятора.
     */
    public function guardAgainstHighLoad(): void
    {
        $intelligence = App::make(SwarmIntelligence::class);
        $metrics = App::make(MetricsCollector::class)->getMetrics();
        
        $dropChance = $intelligence->computeSheddingRate($metrics);

        if ($dropChance > 0 && random_int(1, 100) <= $dropChance) {
            throw new HiveOvercapacityException(
                message: "Action declined: Swarm PID protection active ({$dropChance}%)",
                health: (int)$dropChance
            );
        }
    }

    public function isHiveDistressed(): bool
    {
        $intelligence = App::make(SwarmIntelligence::class);
        $metrics = App::make(MetricsCollector::class)->getMetrics();
        
        return $intelligence->computeSheddingRate($metrics) > 0;
    }
}

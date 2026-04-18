<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Http\Middleware;

use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Closure;
use Illuminate\Http\Request;

final class AltruismMiddleware
{
    public function __construct(
        private readonly StateRepository $repository,
        private readonly MetricsCollector $collector,
        private readonly SwarmIntelligence $intelligence
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // 1. Исключения и статус
        if (!config('hive-mind.shedding.enabled', true) || $request->is(config('hive-mind.shedding.except', []))) {
            return $next($request);
        }

        // 2. Сбор локальных метрик и обновление состояния ноды в кластере
        $metrics = $this->collector->getMetrics();
        $this->repository->updateLocal($metrics);

        // 3. Вычисление шанса отсечения через ПИД-регулятор (Интеллект Роя)
        $dropChance = $this->intelligence->computeSheddingRate($metrics);

        // 4. Принятие решения
        if ($dropChance > 0 && random_int(1, 100) <= $dropChance) {
            throw new HiveOvercapacityException(
                message: "Swarm PID protection active ({$dropChance}%)",
                health: (int)$dropChance,
                retryAfter: (int)config('hive-mind.shedding.retry_after', 60)
            );
        }

        return $next($request);
    }
}

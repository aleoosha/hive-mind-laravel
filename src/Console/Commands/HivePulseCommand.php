<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Console\Commands;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\DTO\SwarmSnapshot;
use Aleoosha\HiveMind\Services\MetricsAccumulator;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class HivePulseCommand extends Command
{
    protected $signature = 'hive:pulse';
    protected $description = 'Broadcasting node health and archiving Swarm history using PID analysis';

    private bool $shouldQuit = false;

    public function handle(
        MetricsCollector $collector,
        StateRepository $repository,
        SwarmIntelligence $intelligence,
        MetricsAccumulator $accumulator
    ): int {
        $this->info('HiveMind: Swarm Consciousness active...');

        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, fn() => $this->shouldQuit = true);
            pcntl_signal(SIGTERM, fn() => $this->shouldQuit = true);
        }

        $lastArchiveTime = time();
        $interval = (int)config('hive-mind.broadcast.interval_seconds', 1);

        while (!$this->shouldQuit) {
            // 1. Слой восприятия: Снимаем метрики
            $metrics = $collector->getMetrics();

            // 2. Слой коммуникации: Обновляем Heartbeat в Redis
            $repository->updateLocal($metrics);

            // 3. Слой интеллекта: Расчет ПИД-коэффициентов
            $intelligence->computeSheddingRate($metrics);

            // 4. Слой памяти: Накапливаем данные для минутного архива
            $accumulator->push(
                $repository->getGlobalHealth(),
                $metrics
            );
            
            $activeNodes = count(\Illuminate\Support\Facades\Redis::keys('hive_node:*'));

            // 5. Слой архивации: Запись в SQL раз в минуту
            if (time() - $lastArchiveTime >= 60) {
                $this->archive($accumulator->flush($activeNodes));
                $lastArchiveTime = time();
            }

            $this->line(sprintf(
                "[%s] Node: %s | CPU: %s%% | DB: %s ms | PID Output: %s",
                now()->toTimeString(),
                $metrics->nodeId,
                $metrics->cpu,
                $metrics->dbLatency,
                $intelligence->computeSheddingRate($metrics)
            ));

            sleep($interval);
        }

        $this->info('HiveMind: Graceful shutdown complete.');
        
        return self::SUCCESS;
    }

    /**
     * Сохранение агрегированного снимка в базу данных.
     * Используется для Capacity Planning и анализа трендов.
     */
    private function archive(SwarmSnapshot $snapshot): void
    {
        if ($snapshot->sampleCount === 0) {
            return;
        }

        try {
            DB::table('hive_snapshots')->insert([
                'health_score' => $snapshot->avgHealth,
                'db_latency_max' => $snapshot->maxDbLatency,
                'sample_count' => $snapshot->sampleCount,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // В случае падения БД, процесс не должен прерываться.
            // Ошибка игнорируется, чтобы сохранить работу Роя.
        }
    }
}

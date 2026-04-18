<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Console\Commands;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\DTO\HardwareContext;
use Aleoosha\HiveMind\DTO\SwarmSnapshot;
use Aleoosha\HiveMind\Services\MetricsAccumulator;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
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
        $this->registerSignals();

        $lastArchiveTime = time();
        $interval = (int)config('hive-mind.broadcast.interval_seconds', 1);
        $hardware = $collector->getHardwareContext();

        while (!$this->shouldQuit) {
            $metrics = $collector->getMetrics();
            $sheddingRate = $intelligence->computeSheddingRate($metrics);
            
            $repository->updateLocal($metrics);
            $accumulator->push($repository->getGlobalHealth(), $metrics, $sheddingRate);

            $nodes = count(Redis::keys('hive_node:*'));

            if (time() - $lastArchiveTime >= 60) {
                $this->archive($accumulator->flush($nodes), $hardware);
                $lastArchiveTime = time();
            }

            $this->displayPulse($nodes, $metrics, $sheddingRate);
            sleep($interval);
        }

        $this->info('HiveMind: Graceful shutdown complete.');
        return self::SUCCESS;
    }

    private function registerSignals(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, fn() => $this->shouldQuit = true);
            pcntl_signal(SIGTERM, fn() => $this->shouldQuit = true);
        }
    }

    private function displayPulse(int $nodes, $metrics, float $rate): void
    {
        $pidOutput = $rate > 0 ? "<fg=red>{$rate}</>%" : "<fg=green>0</>%";
        
        $this->line(sprintf(
            "[%s] 🐝 Nodes: %d | 🖥️ CPU: %s%% | 🧠 RAM: %s%% | 🗄️ DB: %s ms | 📢 PID: %s",
            now()->toTimeString(),
            $nodes,
            $metrics->cpu,
            $metrics->memory,
            $metrics->dbLatency,
            $pidOutput
        ));
    }

    private function archive(SwarmSnapshot $snapshot, HardwareContext $hardware): void
    {
        if ($snapshot->sampleCount === 0) return;

        try {
            DB::table('hive_snapshots')->insert(array_merge(
                $snapshot->toArray(),
                $hardware->toArray(),
                ['created_at' => now()]
            ));
            $this->info("Snapshot saved!");
        } catch (Throwable $e) {
            $this->error("Archive Error: " . $e->getMessage());
        }
    }
}

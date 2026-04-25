<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Console\Commands;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\HiveMind\Services\MetricsAccumulator;
use Aleoosha\HiveMind\Support\DatabaseMapper;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\Telemetry\Contracts\DTO\HardwareContext;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Main control loop for the HiveMind system.
 * Orchestrates telemetry collection, decision making, and data archiving.
 */
final class HivePulseCommand extends Command
{
    protected $signature = 'hive:pulse';

    protected $description = 'Broadcasting node health and archiving Swarm history using PID analysis';

    private bool $shouldQuit = false;

    public function handle(
        MetricsCollectorInterface $collector,
        PidStateRepositoryInterface $pidRepository,
        DecisionEngine $engine,
        MetricsAccumulator $accumulator
    ): int {
        $this->info('HiveMind: Swarm Consciousness active (FixedPoint Edition)...');

        $this->registerSignals();

        $lastArchiveTime = time();
        $interval = (int) config('hive-mind.broadcast.interval_seconds', 1);

        /** @var HardwareContext $hardware */
        $hardware = $collector->getHardwareContext();

        while (! $this->shouldQuit) {
            try {
                // 1. Collect telemetry metrics from the underlying system
                $metrics = $collector->collect();

                // 2. Fetch the persistent PID state for decision context
                $previousState = $pidRepository->getState('global_resilience');

                // 3. Process metrics through the Decision Support System (DSS)
                $decision = $engine->evaluate($metrics, $previousState);

                // 4. Push data to accumulator for periodic archiving
                $accumulator->push($metrics, $decision);

                $nodes = count(Redis::keys('hive_node:*'));

                // 5. Archive aggregated snapshots every minute
                if (time() - $lastArchiveTime >= 60) {
                    $this->archive($accumulator->flush($nodes), $hardware);
                    $lastArchiveTime = time();
                }

                $this->displayPulse($nodes, $metrics, $decision->sheddingRate->toFloat());

            } catch (Throwable $e) {
                $this->error('Pulse Loop Error: '.$e->getMessage());
            }

            sleep($interval);
        }

        $this->info('HiveMind: Graceful shutdown complete.');

        return self::SUCCESS;
    }

    /**
     * Register OS signals for graceful shutdown.
     */
    private function registerSignals(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, fn () => $this->shouldQuit = true);
            pcntl_signal(SIGTERM, fn () => $this->shouldQuit = true);
        }
    }

    /**
     * Render the current system state to the console output.
     */
    private function displayPulse(int $nodes, $metrics, float $rate): void
    {
        $pidOutput = $rate > 0 ? "<fg=red>{$rate}</>%" : '<fg=green>0</>%';

        $this->line(sprintf(
            '[%s] 🐝 Nodes: %d | 🖥️ CPU: %s%% | 🧠 RAM: %s%% | 📢 PID: %s',
            now()->toTimeString(),
            $nodes,
            $metrics->cpu->toFloat(),
            $metrics->memory->toFloat(),
            $pidOutput
        ));
    }

    /**
     * Save the aggregated Swarm state to the database.
     */
    private function archive($snapshot, $hardware): void
    {
        try {
            DB::table('hive_snapshots')->insert(array_merge(
                DatabaseMapper::snapshotToDb($snapshot),
                DatabaseMapper::hardwareToDb($hardware),
                ['created_at' => now()]
            ));
            $this->info('Snapshot saved to database.');
        } catch (Throwable $e) {
            $this->error('Archive Error: '.$e->getMessage());
        }
    }
}

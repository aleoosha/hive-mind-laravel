<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Console\Commands;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\HiveMind\Services\MetricsAccumulator;
use Aleoosha\HiveMind\Support\DatabaseMapper;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

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
        $hardware = $collector->getHardwareContext();

        while (! $this->shouldQuit) {
            try {
                $metrics = $collector->collect();
                $decision = $engine->evaluate($metrics);

                $accumulator->push($metrics, $decision);

                // Get active nodes count
                $nodes = count(Redis::keys('hive_node:*'));

                if (time() - $lastArchiveTime >= 60) {
                    $this->archive($accumulator->flush($nodes), $hardware);
                    $lastArchiveTime = time();
                }

                // We pass the RAW float (0.0 - 1.0) to the display method
                $this->displayPulse($nodes, $metrics, $decision->sheddingRate->toFloat());

            } catch (Throwable $e) {
                $this->error('Pulse Loop Error: '.$e->getMessage());
            }

            sleep($interval);
        }

        $this->info('HiveMind: Graceful shutdown complete.');

        return self::SUCCESS;
    }

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
        // Convert 0.01 to 1.00%
        $percent = number_format($rate * 100, 2);

        $pidOutput = $rate > 0
            ? "<fg=red>{$percent}%</>"
            : '<fg=green>0%</>';

        $this->line(sprintf(
            '[%s] 🐝 Nodes: %d | 🖥️ CPU: <fg=green>%s%%</> | 🧠 RAM: <fg=magenta>%s%%</> | 📢 PID: %s',
            now()->toTimeString(),
            $nodes,
            number_format($metrics->cpu->toFloat(), 2),
            number_format($metrics->memory->toFloat(), 2),
            $pidOutput
        ));
    }

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

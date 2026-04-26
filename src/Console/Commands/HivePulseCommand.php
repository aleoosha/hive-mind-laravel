<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Console\Commands;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\HiveMind\Services\MetricsAccumulator;
use Aleoosha\HiveMind\Support\DatabaseMapper;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;
use Illuminate\Console\Command;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * HivePulseCommand orchestrates the real-time monitoring and archiving 
 * of the swarm cluster state.
 */
final class HivePulseCommand extends Command
{
    protected $signature = 'hive:pulse';
    protected $description = 'Broadcasting node health and archiving Swarm history';

    private bool $shouldQuit = false;
    private int $lastArchiveTime;

    public function handle(
        MetricsCollectorInterface $collector,
        DecisionEngine $engine,
        MetricsAccumulator $accumulator,
        RedisManager $redis
    ): int {
        $this->info('HiveMind: Swarm Consciousness active...');
        $this->registerSignals();
        
        $this->lastArchiveTime = time();
        $interval = (int) config('hive-mind.broadcast.interval_seconds', 1);

        while (! $this->shouldQuit) {
            try {
                $this->processCycle($collector, $engine, $accumulator, $redis);
            } catch (Throwable $e) {
                $this->error('Pulse Loop Error: ' . $e->getMessage());
            }

            sleep($interval);
        }

        $this->info('HiveMind: Graceful shutdown complete.');
        return self::SUCCESS;
    }

    /**
     * Executes a single monitoring cycle.
     */
    private function processCycle(
        MetricsCollectorInterface $collector,
        DecisionEngine $engine,
        MetricsAccumulator $accumulator,
        RedisManager $redis
    ): void {
        // 1. Telemetry and Decision Making
        $metrics = $collector->collect();
        $decision = $engine->evaluate($metrics);
        $accumulator->push($metrics, $decision);

        // 2. Discover active nodes in the cluster
        $nodes = $this->getActiveNodesCount($redis);

        // 3. Handle periodic database archiving
        $this->handleArchiving($accumulator, $collector, $nodes);

        // 4. Output state to console
        $this->displayPulse($nodes, $metrics, $decision->sheddingRate->toFloat());
    }

    /**
     * Counts active nodes based on heartbeat keys in Redis.
     */
    private function getActiveNodesCount(RedisManager $redis): int
    {
        try {
            return count($redis->connection()->keys('*hive_node:*'));
        } catch (Throwable) {
            return 0; // Fallback if Redis is momentarily unavailable
        }
    }

    /**
     * Manages minute-by-minute snapshots archiving.
     */
    private function handleArchiving(
        MetricsAccumulator $accumulator, 
        MetricsCollectorInterface $collector, 
        int $nodes
    ): void {
        if (time() - $this->lastArchiveTime >= 60) {
            $this->archive(
                $accumulator->flush($nodes), 
                $collector->getHardwareContext()
            );
            $this->lastArchiveTime = time();
        }
    }

    /**
     * Renders the current system pulse to the console output.
     */
    private function displayPulse(int $nodes, $metrics, float $rate): void
    {
        $percent = number_format($rate * 100, 2);
        $pidOutput = $rate > 0 ? "<fg=red>{$percent}%</>" : '<fg=green>0%</>';

        $this->line(sprintf(
            '[%s] 🐝 Nodes: %d | 🖥️ CPU: <fg=green>%s%%</> | 🧠 RAM: <fg=magenta>%s%%</> | 📢 PID: %s',
            now()->toTimeString(),
            $nodes,
            number_format($metrics->cpu->toFloat(), 2),
            number_format($metrics->memory->toFloat(), 2),
            $pidOutput
        ));
    }

    /**
     * Persists the aggregated Swarm snapshot to the database.
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
            $this->error('Archive Error (Check DB/Migrations): ' . $e->getMessage());
        }
    }

    /**
     * Register OS signals for graceful shutdown handling.
     */
    private function registerSignals(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, fn () => $this->shouldQuit = true);
            pcntl_signal(SIGTERM, fn () => $this->shouldQuit = true);
        }
    }
}

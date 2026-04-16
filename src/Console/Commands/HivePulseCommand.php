<?php

namespace Aleoosha\HiveMind\Console\Commands;

use Illuminate\Console\Command;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Contracts\StateRepository;

class HivePulseCommand extends Command
{
    protected $signature = 'hive:pulse';
    protected $description = 'Test node metrics collection with DTO';

    public function handle(MetricsCollector $collector, StateRepository $repository): int
    {
        $this->info('HiveMind: Broadcasting node health...');

        while (true) {
            $metrics = $collector->getMetrics();
            $repository->updateLocal($metrics);
            
            $this->line(sprintf(
                "[%s] Hive Heartbeat -> CPU: %s%% | Mem: %s%%", 
                now()->toTimeString(), 
                $metrics->cpu,
                $metrics->memory
            ));
            
            sleep(config('hive-mind.broadcast.interval_seconds', 1));
        }
    }
}

<?php

namespace Aleoosha\HiveMind\Console\Commands;

use Illuminate\Console\Command;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Contracts\StateRepository;

class HivePulseCommand extends Command
{
    protected $signature = 'hive:pulse';
    protected $description = 'Run node broadcast loop';

    public function handle(MetricsCollector $collector, StateRepository $repository)
    {
        $this->info('HiveMind: Broadcasting started...');

        while (true) {
            $metrics = $collector->getMetrics();
            $repository->updateLocal($metrics);
            
            $this->line("Metrics sent: CPU {$metrics['cpu']}%", 'info', 'vv');
            
            sleep(config('hive-mind.broadcast.interval_seconds'));
        }
    }
}

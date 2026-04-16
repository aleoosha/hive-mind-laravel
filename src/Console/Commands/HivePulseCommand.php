<?php

namespace Aleoosha\HiveMind\Console\Commands;

use Illuminate\Console\Command;
use Aleoosha\HiveMind\Services\MetricsCollector;

class HivePulseCommand extends Command
{
    protected $signature = 'hive:pulse';
    protected $description = 'Test node metrics collection with DTO';

    public function handle(MetricsCollector $collector): int
    {
        $this->info('HiveMind: Testing metrics collection with DTO...');

        for ($i = 0; $i < 5; $i++) {
            // Теперь $metrics — это объект NodeMetrics
            $metrics = $collector->getMetrics();
            
            $this->line(sprintf(
                "[%s] CPU: %s%% | Mem: %s%% | TS: %s",
                now()->toTimeString(),
                $metrics->cpu,
                $metrics->memory,
                $metrics->timestamp
            ));
            
            sleep(1);
        }

        $this->info('DTO Test completed successfully.');
        
        return self::SUCCESS;
    }
}

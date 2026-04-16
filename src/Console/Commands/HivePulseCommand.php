<?php

namespace Aleoosha\HiveMind\Console\Commands;

use Illuminate\Console\Command;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Contracts\StateRepository;

class HivePulseCommand extends Command
{
    protected $signature = 'hive:pulse';
    protected $description = 'Run node broadcast loop';

    public function handle(MetricsCollector $collector)
    {
        $this->info('HiveMind: Testing metrics collection...');

        // Сделаем 5 циклов замера, чтобы проверить стабильность
        for ($i = 0; $i < 5; $i++) {
            $metrics = $collector->getMetrics();
            
            $this->line(sprintf(
                "[%s] CPU: %s%% | Mem: %s%%",
                now()->toTimeString(),
                $metrics['cpu'],
                $metrics['memory']
            ));
            
            sleep(1);
        }

        $this->info('Test completed successfully.');
        return self::SUCCESS;
    }

}

<?php

namespace Aleoosha\HiveMind\Services;

class MetricsCollector
{
    public function getMetrics(): array
    {
        return [
            'cpu' => $this->getCpuUsage(),
            'memory' => $this->getMemoryUsage(),
            'timestamp' => microtime(true),
        ];
    }

    protected function getCpuUsage(): float
    {
        if (!file_exists('/proc/stat')) {
            return 0.0;
        }

        $stat1 = file_get_contents('/proc/stat');
        usleep(100000); // 100ms sample
        $stat2 = file_get_contents('/proc/stat');

        // Logic for CPU delta calculation will go here
        return 0.0; 
    }

    protected function getMemoryUsage(): float
    {
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        
        return round($mem[2] / $mem[1] * 100, 2);
    }
}

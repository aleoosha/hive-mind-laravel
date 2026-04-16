<?php

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\DTO\NodeMetrics;

class MetricsCollector
{
    public function getMetrics(): NodeMetrics
    {
        return new NodeMetrics(
            cpu: $this->getCpuUsage(),
            memory: $this->getMemoryUsage(),
            timestamp: microtime(true)
        );
    }

    protected function getCpuUsage(): float
    {
        if (!is_readable('/proc/stat')) return 0.0;

        $getStats = function() {
            $data = explode(' ', preg_replace('/\s+/', ' ', trim(file_get_contents('/proc/stat'))));
            return [
                'idle' => (int)$data[4],
                'total' => (int)array_sum(array_slice($data, 1, 7))
            ];
        };

        $stat1 = $getStats();
        usleep(200000);
        $stat2 = $getStats();

        $totalDelta = $stat2['total'] - $stat1['total'];
        $idleDelta = $stat2['idle'] - $stat1['idle'];

        return $totalDelta > 0 
            ? round(100 * ($totalDelta - $idleDelta) / $totalDelta, 2) 
            : 0.0;
    }

    protected function getMemoryUsage(): float
    {
        if (!is_readable('/proc/meminfo')) return 0.0;

        $meminfo = file_get_contents('/proc/meminfo');
        preg_match_all('/(MemTotal|MemAvailable):\s+(\d+)/', $meminfo, $matches);
        
        $stats = array_combine($matches[1], $matches[2]);
        
        $total = (int)($stats['MemTotal'] ?? 0);
        $available = (int)($stats['MemAvailable'] ?? 0);

        if ($total === 0) return 0.0;

        return round((($total - $available) / $total) * 100, 2);
    }
}

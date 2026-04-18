<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\DTO\NodeMetrics;
use Illuminate\Support\Facades\DB;

class MetricsCollector
{
    private static array $lastCpuStats = [];
    private float $dbTotalTime = 0.0;
    private float $apiTotalTime = 0.0;

    public function __construct()
    {
        $this->listenDb();
    }

    /**
     * Автоматический замер всех SQL запросов.
     */
    private function listenDb(): void
    {
        DB::listen(function ($query) {
            $this->dbTotalTime += $query->time;
        });
    }

    /**
     * Ручной замер внешних API через Трейт.
     */
    public function recordApiLatency(float $milliseconds): void
    {
        $this->apiTotalTime += $milliseconds;
    }

    public function getMetrics(): NodeMetrics
    {
        return new NodeMetrics(
            cpu: $this->getCpuUsage(),
            memory: $this->getMemoryUsage(),
            dbLatency: $this->dbTotalTime,
            apiLatency: $this->apiTotalTime,
            timestamp: (int)microtime(true),
            nodeId: config('app.name') . ':' . gethostname()
        );
    }

    protected function getCpuUsage(): float
    {
        if (!is_readable('/proc/stat')) {
            return 0.0;
        }

        $content = file_get_contents('/proc/stat');
        if (!$content) return 0.0;

        $data = explode(' ', preg_replace('/\s+/', ' ', trim($content)));
        $current = [
            'idle' => (int)$data[4],
            'total' => (int)array_sum(array_slice($data, 1, 7))
        ];

        if (empty(self::$lastCpuStats)) {
            self::$lastCpuStats = $current;
            return 0.0;
        }

        $totalDelta = $current['total'] - self::$lastCpuStats['total'];
        $idleDelta = $current['idle'] - self::$lastCpuStats['idle'];
        
        self::$lastCpuStats = $current;

        return $totalDelta > 0 
            ? round(100 * ($totalDelta - $idleDelta) / $totalDelta, 2) 
            : 0.0;
    }

    protected function getMemoryUsage(): float
    {
        if (!is_readable('/proc/meminfo')) {
            $memory = memory_get_usage(true);
            return round($memory / (1024 * 1024), 2); // Fallback в МБ
        }

        $meminfo = file_get_contents('/proc/meminfo');
        preg_match_all('/(MemTotal|MemAvailable):\s+(\d+)/', $meminfo, $matches);
        $stats = array_combine($matches[1], $matches[2]);

        $total = (int)($stats['MemTotal'] ?? 0);
        $available = (int)($stats['MemAvailable'] ?? 0);

        return $total > 0 ? round((($total - $available) / $total) * 100, 2) : 0.0;
    }
}

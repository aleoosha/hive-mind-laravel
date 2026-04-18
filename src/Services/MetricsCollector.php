<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\HiveMind\DTO\HardwareContext;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Illuminate\Support\Facades\DB;

class MetricsCollector
{
    private static array $lastCpuStats = [];
    private float $dbTotalTime = 0.0;
    private float $apiTotalTime = 0.0;

    public function __construct()
    {
        DB::listen(fn($query) => $this->dbTotalTime += $query->time);
    }

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
        $stats = $this->parseProcStat();
        if (!$stats) return 0.0;

        if (empty(self::$lastCpuStats)) {
            self::$lastCpuStats = $stats;
            return 0.0;
        }

        $totalDelta = $stats['total'] - self::$lastCpuStats['total'];
        $idleDelta = $stats['idle'] - self::$lastCpuStats['idle'];
        self::$lastCpuStats = $stats;

        return $totalDelta > 0 ? round(100 * ($totalDelta - $idleDelta) / $totalDelta, 2) : 0.0;
    }

    private function parseProcStat(): ?array
    {
        if (!is_readable('/proc/stat')) return null;
        $data = explode(' ', preg_replace('/\s+/', ' ', trim(file_get_contents('/proc/stat'))));
        
        return [
            'idle' => (int)$data[4],
            'total' => (int)array_sum(array_slice($data, 1, 7))
        ];
    }

    protected function getMemoryUsage(): float
    {
        if (!is_readable('/proc/meminfo')) {
            return round(memory_get_usage(true) / 1048576, 2);
        }

        $meminfo = file_get_contents('/proc/meminfo');
        preg_match_all('/(MemTotal|MemAvailable):\s+(\d+)/', $meminfo, $matches);
        $stats = array_combine($matches[1], $matches[2]);

        $total = (int)($stats['MemTotal'] ?? 0);
        $available = (int)($stats['MemAvailable'] ?? 0);

        return $total > 0 ? round((($total - $available) / $total) * 100, 2) : 0.0;
    }

    public function getHardwareContext(): HardwareContext
    {
        $cores = is_readable('/proc/cpuinfo') ? (int) shell_exec('nproc') : 1;

        return new HardwareContext(
            cpuCores: $cores ?: 1,
            ramTotalGb: round($this->getMemoryLimit() / 1073741824, 2),
            os: PHP_OS,
            phpVersion: PHP_VERSION
        );
    }

    public function getMemoryLimit(): float
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1' || !$limit) return 1073741824;

        $value = (float)$limit;
        $unit = strtoupper(substr($limit, -1));

        return match ($unit) {
            'G' => $value * 1073741824,
            'M' => $value * 1048576,
            'K' => $value * 1024,
            default => $value,
        };
    }
}

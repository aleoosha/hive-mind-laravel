<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Services;

use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\Telemetry\Contracts\DTO\HardwareContext;
use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;
use Illuminate\Support\Facades\DB;

/**
 * Concrete implementation of system telemetry collection for Laravel environment.
 * Collects CPU, RAM, and database latencies.
 */
class MetricsCollector implements MetricsCollectorInterface
{
    private static array $lastCpuStats = [];

    private float $dbTotalTime = 0.0;

    private float $apiTotalTime = 0.0;

    public function __construct()
    {
        /** Listen to database queries to track total DB execution time */
        DB::listen(fn ($query) => $this->dbTotalTime += $query->time);
    }

    /**
     * Record external API response time to include in telemetry.
     */
    public function recordApiLatency(float $milliseconds): void
    {
        $this->apiTotalTime += $milliseconds;
    }

    /**
     * Collect real-time metrics and return as a standardized DTO.
     */
    public function collect(): NodeMetrics
    {
        return new NodeMetrics(
            cpu: FixedPoint::fromFloat($this->getCpuUsage()),
            memory: FixedPoint::fromFloat($this->getMemoryUsage()),
            dbLatency: FixedPoint::fromFloat($this->dbTotalTime),
            apiLatency: FixedPoint::fromFloat($this->apiTotalTime),
            timestampMs: (int) (microtime(true) * 1000),
            nodeId: config('app.name').':'.gethostname()
        );
    }

    /**
     * Get basic hardware information of the host.
     */
    public function getHardwareContext(): HardwareContext
    {
        $cores = is_readable('/proc/cpuinfo') ? (int) shell_exec('nproc') : 1;

        return new HardwareContext(
            cpuCores: $cores ?: 1,
            ramTotalGb: FixedPoint::fromFloat(round($this->getMemoryLimit() / 1073741824, 2)),
            os: PHP_OS,
            phpVersion: PHP_VERSION
        );
    }

    /**
     * Calculate current CPU usage percentage based on /proc/stat.
     */
    protected function getCpuUsage(): float
    {
        $stats = $this->parseProcStat();
        if (! $stats) {
            return 0.0;
        }

        if (empty(self::$lastCpuStats)) {
            self::$lastCpuStats = $stats;

            return 0.0;
        }

        $totalDelta = $stats['total'] - self::$lastCpuStats['total'];
        $idleDelta = $stats['idle'] - self::$lastCpuStats['idle'];

        self::$lastCpuStats = $stats;

        return $totalDelta > 0 ? round(100 * ($totalDelta - $idleDelta) / $totalDelta, 2) : 0.0;
    }

    /**
     * Parse Linux system CPU statistics.
     */
    private function parseProcStat(): ?array
    {
        if (! is_readable('/proc/stat')) {
            return null;
        }

        $content = file_get_contents('/proc/stat');
        if (! $content) {
            return null;
        }

        $data = explode(' ', preg_replace('/\s+/', ' ', trim($content)));

        return [
            'idle' => (int) $data[4],
            'total' => (int) array_sum(array_slice($data, 1, 7)),
        ];
    }

    /**
     * Calculate current Memory usage percentage.
     */
    protected function getMemoryUsage(): float
    {
        if (! is_readable('/proc/meminfo')) {
            return round(memory_get_usage(true) / 1048576, 2);
        }

        $meminfo = file_get_contents('/proc/meminfo');
        preg_match_all('/(MemTotal|MemAvailable):\s+(\d+)/', $meminfo, $matches);

        $stats = array_combine($matches[1], $matches[2]);
        $total = (int) ($stats['MemTotal'] ?? 0);
        $available = (int) ($stats['MemAvailable'] ?? 0);

        return $total > 0 ? round((($total - $available) / $total) * 100, 2) : 0.0;
    }

    /**
     * Determine the PHP memory limit in bytes.
     */
    public function getMemoryLimit(): float
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1' || ! $limit) {
            return 1073741824;
        } // Default to 1GB if no limit

        $value = (float) $limit;
        $unit = strtoupper(substr($limit, -1));

        return match ($unit) {
            'G' => $value * 1073741824,
            'M' => $value * 1048576,
            'K' => $value * 1024,
            default => $value,
        };
    }
}

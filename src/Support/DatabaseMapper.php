<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Support;

use Aleoosha\Telemetry\Contracts\DTO\HardwareContext;

/**
 * DatabaseMapper - Responsible for converting clean DTOs
 * into raw array formats compatible with Laravel's Query Builder.
 */
class DatabaseMapper
{
    /**
     * Map HardwareContext to hive_snapshots database columns.
     */
    public static function hardwareToDb(HardwareContext $hw): array
    {
        return [
            'cpu_cores' => $hw->cpuCores,
            'ram_total_gb' => $hw->ramTotalGb->value, // stored as integer (e.g. 16000)
            'server_os' => $hw->os,
            'php_version' => $hw->phpVersion,
        ];
    }

    /**
     * Map SwarmSnapshot (from MetricsAccumulator) to hive_snapshots database columns.
     *
     * @param  mixed  $snapshot  The snapshot object produced by MetricsAccumulator
     */
    public static function snapshotToDb(mixed $snapshot): array
    {
        return [
            'avg_health' => (int) ($snapshot->avgHealth * 1000),
            'avg_cpu' => (int) ($snapshot->avgCpu * 1000),
            'max_cpu' => (int) ($snapshot->maxCpu * 1000),
            'avg_db_latency' => (int) ($snapshot->avgDbLatency * 1000),
            'max_db_latency' => (int) ($snapshot->maxDbLatency * 1000),
            'avg_api_latency' => (int) ($snapshot->avgApiLatency * 1000),
            'max_api_latency' => (int) ($snapshot->maxApiLatency * 1000),
            'shedding_rate' => (int) ($snapshot->avgShedding * 1000),
            'thresholds_snapshot' => $snapshot->thresholdsSnapshot,
            'sample_count' => $snapshot->sampleCount,
            'node_count' => $snapshot->nodeCount,
        ];
    }
}

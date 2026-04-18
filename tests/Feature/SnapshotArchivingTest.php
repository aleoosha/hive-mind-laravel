<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\HiveMind\DTO\SwarmSnapshot;
use Aleoosha\HiveMind\DTO\HardwareContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it correctly stores full snapshot context in database', function () {
    $hardware = new HardwareContext(
        cpuCores: 4, 
        ramTotalGb: 8.0, 
        os: 'Linux', 
        phpVersion: '8.1'
    );

    $snapshot = new SwarmSnapshot(
        avgHealth: 50.0,
        avgCpu: 40.0,
        maxCpu: 60.0,
        avgDbLatency: 10.0,
        maxDbLatency: 20.0,
        avgApiLatency: 100.0,
        maxApiLatency: 200.0,
        avgShedding: 25.0,
        thresholdsSnapshot: json_encode(['cpu' => 80]),
        sampleCount: 60,
        nodeCount: 1
    );

    DB::table('hive_snapshots')->insert([
        'avg_health'     => $snapshot->avgHealth,
        'shedding_rate'  => $snapshot->avgShedding,
        'avg_cpu'        => $snapshot->avgCpu,
        'max_cpu'        => $snapshot->maxCpu,
        'avg_db_latency' => $snapshot->avgDbLatency,
        'max_db_latency' => $snapshot->maxDbLatency,
        'avg_api_latency'=> $snapshot->avgApiLatency,
        'max_api_latency'=> $snapshot->maxApiLatency,
        'thresholds_snapshot' => $snapshot->thresholdsSnapshot,
        'sample_count'   => $snapshot->sampleCount,
        'node_count'     => $snapshot->nodeCount,
        'cpu_cores'      => $hardware->cpuCores,
        'ram_total_gb'      => $hardware->ramTotalGb,
        'server_os'      => $hardware->os,
        'php_version'    => $hardware->phpVersion,
        'created_at'     => now(),
    ]);

    $record = DB::table('hive_snapshots')->first();

    expect($record)->not->toBeNull()
        ->and((float)$record->avg_health)->toBe(50.0)
        ->and((int)$record->cpu_cores)->toBe(4)
        ->and((float)$record->shedding_rate)->toBe(25.0);
});


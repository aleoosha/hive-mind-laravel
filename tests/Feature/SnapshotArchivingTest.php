<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\HiveMind\DTO\SwarmSnapshot;
use Aleoosha\HiveMind\Support\DatabaseMapper;
use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\Telemetry\Contracts\DTO\HardwareContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('it correctly stores full snapshot context in database', function () {
    // 1. Setup Data using new DTO structures and FixedPoint
    $hardware = new HardwareContext(
        cpuCores: 4,
        ramTotalGb: FixedPoint::fromFloat(8.0),
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

    // 2. Perform DB Insertion
    // We use toDatabaseArray() which we should have in our Bridge DTOs
    // to convert FixedPoint objects/floats to DB-friendly integers.
    DB::table('hive_snapshots')->insert(array_merge(
        DatabaseMapper::snapshotToDb($snapshot),
        DatabaseMapper::hardwareToDb($hardware),
        ['created_at' => now()]
    ));

    // 3. Verify Database Integrity
    $record = DB::table('hive_snapshots')->first();

    expect($record)->not->toBeNull()
        ->and((new FixedPoint((int) $record->avg_health))->toFloat())->toBe(50.0)
        ->and((new FixedPoint((int) $record->shedding_rate))->toFloat())->toBe(25.0)
        ->and((int) $record->cpu_cores)->toBe(4);
});

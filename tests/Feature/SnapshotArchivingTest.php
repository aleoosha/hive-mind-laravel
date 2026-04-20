<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\HiveMind\DTO\SwarmSnapshot;
use Aleoosha\HiveMind\DTO\HardwareContext;
use Aleoosha\HiveMind\Support\FixedPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it correctly stores full snapshot context in database', function () {
    $hardware = new HardwareContext(4, 8.0, 'Linux', '8.1');
    
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

    DB::table('hive_snapshots')->insert(array_merge(
        $snapshot->toArray(),
        $hardware->toArray(),
        ['created_at' => now()]
    ));

    $record = DB::table('hive_snapshots')->first();

    expect($record)->not->toBeNull()
        ->and(FixedPoint::raw((int)$record->avg_health)->toFloat())->toBe(50.0)
        ->and(FixedPoint::raw((int)$record->shedding_rate)->toFloat())->toBe(25.0)
        ->and((int)$record->cpu_cores)->toBe(4);
});


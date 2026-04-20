<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\DTO\FixedPidResult;
use Aleoosha\HiveMind\DTO\HardwareContext;
use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Aleoosha\HiveMind\Support\FixedPoint;
use Aleoosha\HiveMind\Traits\AsHiveMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;

class TestOrder extends Model
{
    use AsHiveMember;

    protected $fillable = ['name'];
}

test('it prevents model saving when hive is stressed via PID', function () {
    Schema::create('test_orders', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    $pidRepo = Mockery::mock(PidStateRepository::class);
    $zero = FixedPoint::raw(0);

    $pidRepo->shouldReceive('getState')->andReturn(new FixedPidResult(
        output: $zero,
        lastError: $zero,
        integral: $zero,
        timestamp: microtime(true),
        kp: FixedPoint::fromFloat(0.6),
        ki: FixedPoint::fromFloat(0.1),
        kd: FixedPoint::fromFloat(0.4)
    ));
    $pidRepo->shouldReceive('saveState');
    $this->app->instance(PidStateRepository::class, $pidRepo);

    $collector = Mockery::mock(MetricsCollector::class);
    $collector->shouldReceive('getMetrics')->andReturn(new NodeMetrics(
        cpu: 95.0,
        memory: 50.0,
        dbLatency: 0.0,
        apiLatency: 0.0,
        timestamp: (int) microtime(true),
        nodeId: 'test-node'
    ));

    $collector->shouldReceive('getHardwareContext')->andReturn(
        new HardwareContext(4, 8.0, 'Linux', '8.2')
    );
    $this->app->instance(MetricsCollector::class, $collector);

    $intelligence = Mockery::mock(SwarmIntelligence::class);
    $intelligence->shouldReceive('computeSheddingRate')->andReturn(100.0);
    $this->app->instance(SwarmIntelligence::class, $intelligence);

    try {
        TestOrder::create(['name' => 'iPhone 15']);
    } catch (HiveOvercapacityException $e) {
        expect($e->getMessage())->toContain('Swarm PID protection active (100%)');

        return;
    }

    $this->fail('HiveOvercapacityException was not thrown by Trait protection');
});

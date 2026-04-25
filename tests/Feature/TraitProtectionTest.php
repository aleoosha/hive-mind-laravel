<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\DssCore\DTO\DecisionResult;
use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\TauPid\Contracts\DTO\FixedPidResult;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;
use Aleoosha\Telemetry\Contracts\DTO\NodeMetrics;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\HiveMind\Traits\AsHiveMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;

/**
 * Dummy model for testing Trait protection.
 */
class TestOrder extends Model
{
    use AsHiveMember;
    protected $fillable = ['name'];
}

test('it prevents model saving when hive is stressed via PID', function () {
    // 1. Database Setup
    Schema::create('test_orders', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    $zero = new FixedPoint(0);
    $now = (int)(microtime(true) * 1000);

    // 2. Mock PID Repository (using Interface)
    $pidRepo = Mockery::mock(PidStateRepositoryInterface::class);
    $pidRepo->shouldReceive('getState')->andReturn(new FixedPidResult(
        output: $zero,
        lastError: $zero,
        integral: $zero,
        timestampMs: $now,
        kp: $zero, ki: $zero, kd: $zero
    ));
    // Important: bind to Interface, not class
    $this->app->instance(PidStateRepositoryInterface::class, $pidRepo);

    // 3. Mock Metrics Collector (using Interface)
    $collector = Mockery::mock(MetricsCollectorInterface::class);
    $collector->shouldReceive('collect')->andReturn(new NodeMetrics(
        cpu: FixedPoint::fromFloat(95.0),
        memory: FixedPoint::fromFloat(50.0),
        dbLatency: $zero,
        apiLatency: $zero,
        timestampMs: $now,
        nodeId: 'test-node'
    ));
    $this->app->instance(MetricsCollectorInterface::class, $collector);

    // 4. Mock Decision Engine (The Brain)
    $engine = Mockery::mock(DecisionEngine::class);
    $engine->shouldReceive('evaluate')->andReturn(new DecisionResult(
        systemLoad: FixedPoint::fromFloat(0.95),
        sheddingRate: FixedPoint::fromFloat(1.0), // 100% rejection chance
        alerts: ['cpu_percent'],
        timestampMs: $now
    ));
    $this->app->instance(DecisionEngine::class, $engine);

    // 5. Execution & Assertion
    try {
        // This should trigger the static::saving() hook in AsHiveMember trait
        TestOrder::create(['name' => 'iPhone 15']);
    } catch (HiveOvercapacityException $e) {
        // We expect 100.0 from FixedPoint::fromFloat(1.0)->toFloat() * 100
        expect($e->getMessage())->toContain('Swarm DSS protection active (Shedding: 100%)');
        return;
    }

    $this->fail('HiveOvercapacityException was not thrown by Trait protection');
});

<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\DTO\PidResult;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\Traits\AsHiveMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;

class TestOrder extends Model {
    use AsHiveMember;
    protected $fillable = ['name'];
}

test('it prevents model saving when hive is stressed via PID', function () {
    Schema::create('test_orders', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    // 1. Мокаем репозиторий состояния ПИД (нужен для SwarmIntelligence)
    $pidRepo = Mockery::mock(PidStateRepository::class);
    $pidRepo->shouldReceive('getState')->andReturn(new PidResult(
        output: 0.0, lastError: 0.0, integral: 0.0, timestamp: microtime(true),
        kp: 0.6, ki: 0.1, kd: 0.4 // Передаем коэффициенты
    ));
    $pidRepo->shouldReceive('saveState');
    $this->app->instance(PidStateRepository::class, $pidRepo);

    // 2. Мокаем коллектор (датчики + железо)
    $collector = Mockery::mock(MetricsCollector::class);
    $collector->shouldReceive('getMetrics')->andReturn(new NodeMetrics(
        cpu: 95.0, memory: 50.0, dbLatency: 0.0, apiLatency: 0.0, 
        timestamp: (int)microtime(true), nodeId: 'test-node'
    ));
    // Нужен для HardwareContext в SwarmIntelligence
    $collector->shouldReceive('getHardwareContext')->andReturn(
        new \Aleoosha\HiveMind\DTO\HardwareContext(4, 8.0, 'Linux', '8.2')
    );
    $this->app->instance(MetricsCollector::class, $collector);

    // 3. Мокаем интеллект (Decision Layer)
    $intelligence = Mockery::mock(SwarmIntelligence::class);
    $intelligence->shouldReceive('computeSheddingRate')->andReturn(100.0);
    $this->app->instance(SwarmIntelligence::class, $intelligence);

    // 4. Проверка исключения
    try {
        TestOrder::create(['name' => 'iPhone 15']);
    } catch (HiveOvercapacityException $e) {
        expect($e->getMessage())->toContain('Swarm PID protection active (100%)');
        return;
    }

    $this->fail('HiveOvercapacityException was not thrown by Trait protection');
});

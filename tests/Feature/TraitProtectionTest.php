<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Feature;

use Aleoosha\HiveMind\DTO\NodeMetrics;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Aleoosha\HiveMind\Traits\AsHiveMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;

/**
 * Тестовая модель для проверки трейта в изолированном окружении.
 */
class TestOrder extends Model {
    use AsHiveMember;
    protected $fillable = ['name'];
}

test('it prevents model saving when hive is stressed via PID', function () {
    // 1. Создаем временную таблицу в памяти для теста
    Schema::create('test_orders', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    // 2. Мокаем коллектор (Слой восприятия)
    // Имитируем, что датчики фиксируют критическую нагрузку
    $collector = Mockery::mock(MetricsCollector::class);
    $collector->shouldReceive('getMetrics')->andReturn(new NodeMetrics(
        cpu: 95.0,
        memory: 95.0,
        dbLatency: 1000.0,
        apiLatency: 0.0,
        timestamp: time(),
        nodeId: 'test-node-trait'
    ));
    $this->app->instance(MetricsCollector::class, $collector);

    // 3. Мокаем интеллект (Слой принятия решений)
    // Устанавливаем 100% шанс отсечения, чтобы исключить рандом
    $intelligence = Mockery::mock(SwarmIntelligence::class);
    $intelligence->shouldReceive('computeSheddingRate')->andReturn(100.0);
    $this->app->instance(SwarmIntelligence::class, $intelligence);

    // 4. Попытка сохранить модель должна вызвать исключение
    try {
        TestOrder::create(['name' => 'iPhone 15']);
    } catch (HiveOvercapacityException $e) {
        // Проверяем, что исключение содержит актуальный сигнал от ПИД-регулятора
        expect($e->getMessage())->toContain('Swarm PID protection active (100%)');
        return;
    }

    $this->fail('HiveOvercapacityException was not thrown by Trait protection');
});

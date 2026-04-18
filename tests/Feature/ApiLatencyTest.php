<?php

declare(strict_types=1);

use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Traits\AsHiveMember;

test('hiveExternalCall records execution time correctly', function () {
    $collector = app(MetricsCollector::class);
    
    $member = new class { 
        use AsHiveMember; 
        
        public function callExternal(callable $callback): mixed 
        {
            return $this->hiveExternalCall($callback);
        }
    };

    // Выполняем имитацию долгого запроса (50мс)
    $member->callExternal(function() {
        usleep(50000); 
        return 'success';
    });

    $metrics = $collector->getMetrics();

    // Проверяем, что замер зафиксирован (с учетом погрешности системы)
    expect($metrics->apiLatency)->toBeGreaterThanOrEqual(50)
        ->and($metrics->apiLatency)->toBeLessThan(100);
});

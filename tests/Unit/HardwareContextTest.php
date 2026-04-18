<?php

declare(strict_types=1);

use Aleoosha\HiveMind\DTO\HardwareContext;
use Aleoosha\HiveMind\Services\MetricsCollector;

test('it creates hardware context with correct types', function () {
    $context = new HardwareContext(
        cpuCores: 8,
        ramTotalGb: 16.0,
        os: 'Linux',
        phpVersion: '8.2.0'
    );

    expect($context->cpuCores)->toBe(8)
        ->and($context->ramTotalGb)->toBe(16.0)
        ->and($context->os)->toBe('Linux');
});

test('metrics collector gathers real hardware info', function () {
    $collector = new MetricsCollector();
    $context = $collector->getHardwareContext();

    expect($context->cpuCores)->toBeGreaterThan(0)
        ->and($context->ramTotalGb)->toBeGreaterThan(0)
        ->and($context->phpVersion)->toBe(PHP_VERSION);
});

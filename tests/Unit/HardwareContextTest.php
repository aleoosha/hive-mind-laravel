<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests\Unit;

use Aleoosha\Telemetry\Contracts\DTO\HardwareContext;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\Support\Types\FixedPoint;

test('it creates hardware context with correct types', function () {
    // In the new architecture, ramTotalGb is a FixedPoint object
    $context = new HardwareContext(
        cpuCores: 8,
        ramTotalGb: FixedPoint::fromFloat(16.0),
        os: 'Linux',
        phpVersion: '8.1.0'
    );

    expect($context->cpuCores)->toBe(8)
        ->and($context->ramTotalGb->toFloat())->toBe(16.0)
        ->and($context->os)->toBe('Linux');
});

test('metrics collector gathers real hardware info', function () {
    // We use the real service here to check system calls (nproc, etc.)
    $collector = new MetricsCollector();
    $context = $collector->getHardwareContext();

    expect($context->cpuCores)->toBeGreaterThan(0)
        ->and($context->ramTotalGb->toFloat())->toBeGreaterThan(0)
        ->and($context->phpVersion)->toBe(PHP_VERSION);
});

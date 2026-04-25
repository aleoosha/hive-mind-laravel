<?php

declare(strict_types=1);

use Aleoosha\HiveMind\Traits\AsHiveMember;
use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;

test('hiveExternalCall records execution time correctly', function () {
    /** @var MetricsCollectorInterface $collector */
    $collector = app(MetricsCollectorInterface::class);

    // Create an anonymous class to use the trait
    $member = new class
    {
        use AsHiveMember;

        public function callExternal(callable $callback): mixed
        {
            return $this->hiveExternalCall($callback);
        }
    };

    // Simulate a long API request (50ms)
    $member->callExternal(function () {
        usleep(50000);

        return 'success';
    });

    // Get fresh metrics
    $metrics = $collector->collect();

    // Check apiLatency via toFloat() or comparing FixedPoint values
    // In new architecture, it should be >= 50.0 ms
    expect($metrics->apiLatency->toFloat())->toBeGreaterThanOrEqual(50.0)
        ->and($metrics->apiLatency->toFloat())->toBeLessThan(100.0);
});

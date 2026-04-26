# HiveMind Laravel 🐝

Distributed load shedding and cluster health synchronization for Laravel applications based on bio-inspired self-regulation patterns.

## Technical Overview

The library provides a mechanism for distributed monitoring of node health and proactive traffic management. Unlike traditional load balancers, HiveMind allows each application instance to make autonomous decisions about request processing based on the aggregate state of the entire cluster.

### Key Architecture Components
*   **Autonomous PID Regulation**: Implements a Proportional-Integral-Derivative controller to handle traffic spikes and prevent system resonance.
*   **Physical Dynamics Tuning**: Configure protection using real-world parameters: *Settling Time*, *Activation Margin*, and *Hard Limits*.
*   **Self-Tuning Intelligence**: An adaptive tuner that automatically calibrates coefficients based on hardware capacity and real-time oscillations.
*   **Fail-Safe Architecture**: Automatic fallback to Null-drivers if Redis or Database is unavailable, ensuring the library is never a single point of failure.
*   **High-Performance Octane Support**: Native integration with Laravel Octane and Swoole Tables for ultra-low latency telemetry sharing.

## Installation

Add the repository to your `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com"
    }
]
```

Then run:
```bash
composer require aleoosha/hive-mind-laravel
```

## Configuration

Publish the configuration file to define system thresholds:

```bash
php artisan vendor:publish --tag=hive-mind-config
```

### Resource Thresholds & Dynamics
Configure how the "Swarm" reacts to different resource stresses:

```php
'thresholds' => [
    'cpu_percent' => [
        'limit' => 70,
        'activation_margin' => 0.8,
        'settling_time' => 2,
    ],
    'db_latency_ms' => [
        'limit' => 200,
        'activation_margin' => 0.9,
        'settling_time' => 10,
    ],
],
```

### Laravel Octane (Swoole) Integration
For maximum performance, HiveMind can use **Swoole Tables** (shared memory) instead of Redis. Add this to your `config/octane.php`:

```php
'tables' => [
    'hive_telemetry' => [
        'size' => 100,
        'columns' => [
            'cpu' => 'int',
            'ram' => 'int',
            'updated_at' => 'int',
        ],
    ],
],
```

## Usage

### Node Broadcast
To start the health broadcasting and background monitoring process:
```bash
php artisan hive:pulse
```

### Visual Diagnostics
Visualize the transition processes and hysteresis loops directly in the CLI:
```bash
php artisan hive:debug-chart
```

### Protection Layer
Register the middleware to enable automated load shedding. In **Laravel 11** (`bootstrap/app.php`):

```php
$middleware->append(\Aleoosha\HiveMind\Http\Middleware\AltruismMiddleware::class);
```

## Resilience & Fail-Safe
HiveMind is designed with **Graceful Degradation**:
1.  **Storage Failure**: If Redis is down, the library automatically switches to `NullStateRepository`. Protection is disabled, but the website stays online.
2.  **Runtime Errors**: All internal logic is wrapped in safety blocks. Any mathematical or infrastructure error is logged, and the request is allowed to pass through.

## Development and Testing

The project uses **Pest** for unit testing and **Orchestra Testbench** for integration testing.

```bash
./vendor/bin/pest
```

## License
MIT

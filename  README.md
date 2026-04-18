# HiveMind Laravel

Distributed load shedding and cluster health synchronization for Laravel applications based on bio-inspired self-regulation patterns.

## Technical Overview

The library provides a mechanism for distributed monitoring of node health and proactive traffic management. Unlike traditional load balancers, HiveMind allows each application instance to make autonomous decisions about request processing based on the aggregate state of the entire cluster.

### Key Architecture Components

* **Autonomous PID Regulation**: Implements a Proportional-Integral-Derivative controller to handle traffic spikes and prevent system resonance.
* **Self-Tuning Intelligence**: An adaptive tuner that automatically calibrates PID coefficients (Kp, Ki) based on hardware capacity and real-time oscillations.
* **Hardware-Aware Context**: Automatic scaling of regulation aggressiveness based on CPU core count and available RAM.
* **Time-Series Archiving**: SQL-based snapshotting for long-term capacity planning and historical analysis.
* **Visual Diagnostics**: Built-in ASCII charting tools to visualize transition processes and hysteresis loops directly in the CLI.


## Installation

Add the repository to your composer.json:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/aleoosha/hive-mind-laravel"
    }
]
```

Then run:

composer require aleoosha/hive-mind-laravel

## Configuration

Publish the configuration file to define system thresholds:

```bash
php artisan vendor:publish --tag=hive-mind-config
```

Main configuration parameters:
* thresholds: CPU and Memory limits before a node reports a "distressed" state.
* activation_threshold: The aggregate health score at which the cluster starts shedding load.
* broadcast: Pulse frequency and data retention settings.

## Usage

### Node Broadcast

To start broadcasting node metrics, run the background process:

```bash
php artisan hive:pulse
```

### Visual Diagnostics

Visualize the "Swarm Pulse" and PID stability (hysteresis) using the built-in debugger:
```bash
php artisan hive:debug-chart
```

### Protection Layer

Register the middleware globally or per-route to enable automated load shedding:

In bootstrap/app.php (Laravel 11) or Kernel.php

```php
$middleware->append(\Aleoosha\HiveMind\Http\Middleware\AltruismMiddleware::class);
```

## Internal Logic

1. Collection: Each node samples CPU delta and Memory availability.
2. Standardization: Metrics are encapsulated into immutable DTOs.
3. Synchronization: Data is serialized and pushed to a shared Redis instance with a short TTL (Heartbeat).
4. Consensus: When a request arrives, the middleware calculates the arithmetic mean of all active heartbeats.
5. Regulation: If the calculated stress index exceeds the configured threshold, the request is terminated with a Retry-After header.

## Development and Testing

The project uses Pest for unit testing and Orchestra Testbench for integration testing within the Laravel environment.

```bash
./vendor/bin/pest
```

## License
MIT

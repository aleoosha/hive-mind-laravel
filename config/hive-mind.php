<?php

return [
    'thresholds' => [
        'cpu_percent' => 80,
        'memory_percent' => 90,
        'db_latency_ms' => 500,
    ],

    'broadcast' => [
        'interval_seconds' => 1,
        'driver' => 'redis',
        'format' => env('HIVE_FORMAT', 'json'), // options: json, msgpack
        'ttl_seconds' => 5,
    ],

    'shedding' => [
        'enabled' => true,
        'mode' => 'probabilistic', // options: static, probabilistic
        'activation_threshold' => 75,
        'retry_after' => 60,
        'except' => [
            'telescope*',
            'horizon*',
            'admin/*',
            '_debugbar/*',
        ],
    ],

    'aggregation' => [
        'window_size' => 5, // number of latest heartbeats to average
    ],
];

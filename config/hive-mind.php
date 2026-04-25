<?php

return [

    /*

    |--------------------------------------------------------------------------
    | Broadcast Settings
    |--------------------------------------------------------------------------

    |
    | Configuration for node-to-cluster communication and synchronization.
    |
    */
    'broadcast' => [
        'interval_seconds' => 1,
        /**
         * Serialization format for inter-node communication.
         * Supported: "json", "msgpack"
         */
        'format' => env('HIVE_FORMAT', 'json'),
        'ttl_seconds' => 5,
    ],

    /*

    |--------------------------------------------------------------------------
    | Load Shedding (Cluster Protection)
    |--------------------------------------------------------------------------

    |
    | Settings for the automated traffic regulation system.
    |
    */
    'shedding' => [
        'enabled' => env('HIVE_SHEDDING_ENABLED', true),

        /**
         * Seconds for the HTTP Retry-After header in 503 responses.
         */
        'retry_after' => 60,

        /**
         * Routes that will bypass HiveMind protection layers.
         */
        'except' => [
            'telescope*',
            'horizon*',
            'admin/*',
            '_debugbar/*',
        ],
    ],

    /*

    |--------------------------------------------------------------------------
    | Resource Thresholds & Physical Dynamics
    |--------------------------------------------------------------------------

    |
    | Configuration for hard limits and recovery behavior.
    |

    | "limit"           - The hard red line. At this point, shedding hits ~100%.
    | "activation_margin" - Percentage of limit (0.0-1.0) where PID starts acting.
    | "settling_time"    - Desired time (seconds) to stabilize the system.

    |                      Lower = aggressive/fast reaction, Higher = smooth/slow.
    |
    */
    'thresholds' => [
        'cpu_percent' => [
            'limit' => (int) env('HIVE_THRESHOLD_CPU', 70),
            'activation_margin' => 0.8,
            'settling_time' => 2,
        ],

        'memory_percent' => [
            'limit' => (int) env('HIVE_THRESHOLD_RAM', 90),
            'activation_margin' => 0.95,
            'settling_time' => 5,
        ],

        'db_latency_ms' => [
            'limit' => (int) env('HIVE_THRESHOLD_DB', 150),
            'activation_margin' => 0.9,
            'settling_time' => 10,
        ],

        'api_latency_ms' => [
            'limit' => (int) env('HIVE_THRESHOLD_API', 500),
            'activation_margin' => 0.8,
            'settling_time' => 15,
        ],
    ],
];

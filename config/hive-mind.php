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
         * Protection Aggression Level.
         *
         * Defines how hard the PID controller "hits" when approaching thresholds.
         * Supported: "soft", "balanced", "aggressive", "panic"
         */
        'aggression' => env('HIVE_AGGRESSION', 'balanced'),

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
    | Resource Thresholds (Hard Limits)
    |--------------------------------------------------------------------------

    |
    | Physical hardware limits. When reached, shedding becomes 100%.
    | Reaction starts pre-emptively at 90% of these values.
    |
    */
    'thresholds' => [
        'cpu_percent' => (int) env('HIVE_THRESHOLD_CPU', 70),
        'memory_percent' => (int) env('HIVE_THRESHOLD_RAM', 90),
        'db_latency_ms' => (int) env('HIVE_THRESHOLD_DB', 150),
        'api_latency_ms' => (int) env('HIVE_THRESHOLD_API', 500),
    ],
];

<?php

return [
    /*

    |--------------------------------------------------------------------------
    | Broadcast Settings
    |--------------------------------------------------------------------------

    |
    | Configuration for node-to-cluster communication.
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
         * The shedding algorithm behavior.
         * 
         * "static"        - Rejects all incoming requests immediately after threshold.
         * "probabilistic" - Linearly increases rejection chance from threshold to 100% load.
         */
        'mode' => env('HIVE_SHEDDING_MODE', 'probabilistic'),

        /**
         * Health percentage (0-100) at which protection activates.
         */
        'activation_threshold' => 75,
        
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
    | Resource Thresholds
    |--------------------------------------------------------------------------

    |
    | Physical hardware limits used for health coefficient calculations.
    |
    */
    'thresholds' => [
        'cpu_percent' => 80,
        'memory_percent' => 90,
        'db_latency_ms' => 100, 
        'api_latency_ms' => 500, 
    ],
];

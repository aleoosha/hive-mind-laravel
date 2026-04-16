<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Broadcast Settings
    |--------------------------------------------------------------------------
    */
    'broadcast' => [
        'interval_seconds' => 1,
        'format' => env('HIVE_FORMAT', 'json'), // json или msgpack
        'ttl_seconds' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Load Shedding (Защита от перегрузки)
    |--------------------------------------------------------------------------
    */
    'shedding' => [
        'enabled' => env('HIVE_SHEDDING_ENABLED', true),
        
        'activation_threshold' => 75,
        
        'retry_after' => 60,

        'except' => [
            'telescope*',
            'horizon*',
            'admin/*',
            '_debugbar/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Health Aggregation
    |--------------------------------------------------------------------------
    | Эти параметры зарезервированы для будущих версий (db_latency, window_size)
    */
    'thresholds' => [
        'cpu_percent' => 80,
        'memory_percent' => 90,
    ],
];

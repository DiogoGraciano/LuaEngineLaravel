<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lua Engine Options
    |--------------------------------------------------------------------------
    |
    | General Lua Engine options
    |
    */
    'options' => [
        // cpu_limit in seconds (0 = no cpu_limit)
        'cpu_limit' => 30,
        
        // Maximum memory in bytes (0 = no limit)
        'memory_limit' => 67108864, // 64MB
        
        // Log errors to Laravel log
        'log_errors' => true,

        // Log level for Lua errors
        // 'error', 'warning', 'info', 'debug'
        'log_level' => 'error',
    ],
];


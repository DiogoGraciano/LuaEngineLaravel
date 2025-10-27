<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sandbox Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Lua sandbox environment
    |
    */
    'sandbox' => [
        // Allow access to math functions
        'allow_math' => true,
        
        // Allow access to string functions
        'allow_string' => true,
        
        // Allow access to table functions
        'allow_table' => true,

        // Custom functions that can be called from Lua
        'global_functions' => [
            // Example: 'my_function' => [MyClass::class, 'myMethod'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lua Engine Options
    |--------------------------------------------------------------------------
    |
    | General Lua Engine options
    |
    */
    'options' => [
        // Timeout in seconds (0 = no timeout)
        'timeout' => 30,
        
        // Maximum memory in bytes (0 = no limit)
        'memory_limit' => 67108864, // 64MB
        
        // Log errors to Laravel log
        'log_errors' => true,
        
        // Log level for Lua errors
        // 'error', 'warning', 'info', 'debug'
        'log_level' => 'error',
    ],
];


<?php

namespace DiogoGraciano\LuaEngine\Utils;

use Illuminate\Support\Facades\Log;

/**
 * Utility functions for LuaEngine
 * 
 * @package DiogoGraciano\LuaEngine\Utils
 */
class Utils
{
    /**
     * Parse memory limit string to bytes
     * 
     * @param mixed $limit
     * @return int
     */
    public static function parseMemoryLimit($limit): int
    {
        if (is_numeric($limit)) {
            return (int)$limit;
        }
        
        $limit = trim($limit);
        $unit = strtolower(substr($limit, -1));
        $value = (int)substr($limit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int)$limit;
        }
    }

    /**
     * Log error based on configuration
     * 
     * @param string|array $message
     * @param array $context
     * @param array $optionsConfig
     * @return void
     */
    public static function logError($message, array $context = [], array $optionsConfig = []): void
    {
        if (!($optionsConfig['log_errors'] ?? true)) {
            return;
        }

        $logLevel = $optionsConfig['log_level'] ?? 'error';
        
        if (is_array($message)) {
            $context = $message;
            $message = 'Lua error';
        }

        switch ($logLevel) {
            case 'debug':
                Log::debug($message, $context);
                break;
            case 'info':
                Log::info($message, $context);
                break;
            case 'warning':
                Log::warning($message, $context);
                break;
            case 'error':
            default:
                Log::error($message, $context);
                break;
        }
    }
}


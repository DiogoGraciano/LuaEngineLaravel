<?php

namespace DiogoGraciano\LuaEngine;

use Exception;
use DiogoGraciano\LuaEngine\Contracts\LuaEngineInterface;
use Illuminate\Support\Facades\Log;

/**
 * Lua Engine - Safe Lua script executor
 * 
 * @package DiogoGraciano\LuaEngine
 */
class LuaEngine implements LuaEngineInterface
{
    protected $lua;
    protected array $sandboxConfig;
    protected array $optionsConfig;
    protected $lastError = null;

    public function __construct(array $config = [])
    {
        // Check if Lua extension is installed
        if (!extension_loaded('lua')) {
            throw new Exception('Lua extension is not installed. Run: sudo pecl install lua');
        }

        $this->lua = new \Lua();
        $this->sandboxConfig = $config['sandbox'] ?? [];
        $this->optionsConfig = $config['options'] ?? [];
        $this->setupSandbox();
    }

    /**
     * Setup sandbox by removing dangerous functions
     */
    protected function setupSandbox(): void
    {
        $allowMath = $this->sandboxConfig['allow_math'] ?? true;
        $allowString = $this->sandboxConfig['allow_string'] ?? true;
        $allowTable = $this->sandboxConfig['allow_table'] ?? true;

        // Build sandbox script based on configuration
        $sandboxScript = "-- Configure safe environment\n";
        
        // Always remove dangerous functions
        $sandboxScript .= "os = nil\n";
        $sandboxScript .= "io = nil\n";
        $sandboxScript .= "require = nil\n";
        $sandboxScript .= "dofile = nil\n";
        $sandboxScript .= "loadfile = nil\n";
        $sandboxScript .= "load = nil\n";
        $sandboxScript .= "loadstring = nil\n";
        $sandboxScript .= "debug = nil\n";
        $sandboxScript .= "package = nil\n";
        $sandboxScript .= "module = nil\n";
        $sandboxScript .= "getfenv = nil\n";
        $sandboxScript .= "setfenv = nil\n\n";

        // Explicitly allow basic safe functions globally
        $sandboxScript .= "-- Keep basic safe functions available\n";
        $sandboxScript .= "-- (These are native Lua functions that are safe)\n";
        
        // Conditionally allow modules
        if (!$allowMath) {
            $sandboxScript .= "math = nil\n";
        }
        
        if (!$allowString) {
            $sandboxScript .= "string = nil\n";
        }
        
        if (!$allowTable) {
            $sandboxScript .= "table = nil\n";
        }

        try {
            $this->lua->eval($sandboxScript);
            
            // Register custom allowed functions from configuration
            if (isset($this->sandboxConfig['global_functions']) && is_array($this->sandboxConfig['global_functions'])) {
                foreach ($this->sandboxConfig['global_functions'] as $name => $callback) {
                    $this->registerFunction($name, $callback);
                }
            }
            
        } catch (\Exception $e) {
            $this->logError('Error configuring Lua sandbox: ' . $e->getMessage());
        }
    }

    /**
     * Log error based on configuration
     * 
     * @param string|array $message
     * @param array $context
     */
    protected function logError($message, array $context = []): void
    {
        if (!$this->optionsConfig['log_errors'] ?? true) {
            return;
        }

        $logLevel = $this->optionsConfig['log_level'] ?? 'error';
        
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

    /**
     * Apply resource limits from configuration
     */
    protected function applyResourceLimits(): void
    {
        $timeout = $this->optionsConfig['timeout'] ?? 0;
        if ($timeout > 0) {
            @set_time_limit($timeout);
        }
        
        $memoryLimit = $this->optionsConfig['memory_limit'] ?? 0;
        if ($memoryLimit > 0) {
            $currentMemoryLimit = ini_get('memory_limit');
            $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
            if (is_numeric($currentMemoryLimit)) {
                $currentMemoryLimitBytes = (int)$currentMemoryLimit;
            } else {
                $currentMemoryLimitBytes = $this->parseMemoryLimit($currentMemoryLimit);
            }
            
            if ($memoryLimitBytes < $currentMemoryLimitBytes) {
                ini_set('memory_limit', $memoryLimitBytes);
            }
        }
    }

    /**
     * Parse memory limit string to bytes
     * 
     * @param mixed $limit
     * @return int
     */
    protected function parseMemoryLimit($limit): int
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
     * Execute Lua script with data
     * 
     * @param string $script Lua code
     * @param array $data Data available in the script
     * @return mixed Execution result
     */
    public function execute(string $script, array $data = [])
    {
        try {
            // Apply resource limits
            $this->applyResourceLimits();
            
            // Register data as global 'data' variable
            $this->lua->assign('data', $data);
            
            // Execute script
            $result = $this->lua->eval($script);
            
            // Clear previous error
            $this->lastError = null;
            
            // Return result if available
            return $result;
            
        } catch (\LuaException $e) {
            $this->lastError = $e->getMessage();
            $this->logError('Lua execution error', [
                'message' => $e->getMessage(),
                'script' => $script,
            ]);
            throw new Exception("Lua script error: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logError('Lua execution error', [
                'message' => $e->getMessage(),
                'script' => $script,
            ]);
            throw new Exception("Error executing Lua script: " . $e->getMessage());
        }
    }

    /**
     * Execute a Lua trigger and return true/false
     * 
     * @param string $script
     * @param array $data
     * @return bool
     */
    public function evaluate(string $script, array $data = []): bool
    {
        try {
            // Apply resource limits
            $this->applyResourceLimits();
            
            $this->lua->assign('data', $data);
            
            // For triggers, we expect to return true/false
            $script = "return ($script)";
            
            $result = $this->lua->eval($script);
            
            $this->lastError = null;
            return (bool) $result;
            
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logError('Error evaluating Lua trigger: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate script syntax without executing
     * 
     * @param string $script
     * @return bool
     */
    public function validate(string $script): bool
    {
        try {
            // Try to compile the script
            $testScript = "function test()\n" . $script . "\nend\nreturn test";
            $this->lua->eval($testScript);
            return true;
        } catch (\Exception $e) {
            $this->logError('Invalid Lua script', [
                'error' => $e->getMessage(),
                'script' => $script,
            ]);
            return false;
        }
    }

    /**
     * Register a PHP function that can be called from Lua
     * 
     * @param string $name Function name in Lua
     * @param callable $callback PHP function
     */
    public function registerFunction(string $name, callable $callback): void
    {
        try {
            $this->lua->registerCallback($name, $callback);
        } catch (\Exception $e) {
            $this->logError("Error registering Lua function '{$name}': " . $e->getMessage());
        }
    }

    /**
     * Get the last error
     * 
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Test if extension is available
     * 
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('lua');
    }

    /**
     * Execute script from a file
     * 
     * @param string $filePath Path to Lua file
     * @param array $data Data available in the script
     * @return mixed
     */
    public function executeFile(string $filePath, array $data = [])
    {
        if (!file_exists($filePath)) {
            throw new Exception("Lua file not found: {$filePath}");
        }

        $script = file_get_contents($filePath);
        return $this->execute($script, $data);
    }

    /**
     * Register multiple functions at once
     * 
     * @param array $functions Associative array [name => callback]
     */
    public function registerFunctions(array $functions): void
    {
        foreach ($functions as $name => $callback) {
            $this->registerFunction($name, $callback);
        }
    }

    /**
     * Clear Lua global variables
     */
    public function clearGlobals(): void
    {
        $this->lua->eval("data = nil");
    }

    /**
     * Assign PHP variable to Lua script
     * 
     * Assigns a PHP variable to the Lua script.
     * Note: Array indices in Lua start at 1.
     * 
     * @param string $name Variable name in Lua
     * @param mixed $value Variable value (array, string, number, etc)
     * @return mixed Returns $this on success or null on failure
     */
    public function assign(string $name, $value)
    {
        try {
            $result = $this->lua->assign($name, $value);
            $this->lastError = null;
            return $result;
        } catch (\LuaException $e) {
            $this->lastError = $e->getMessage();
            $this->logError('Lua assign error', [
                'message' => $e->getMessage(),
                'name' => $name,
            ]);
            return null;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logError('Error assigning Lua variable', [
                'message' => $e->getMessage(),
                'name' => $name,
            ]);
            return null;
        }
    }

    /**
     * Call Lua function
     * 
     * Calls a Lua function with the provided arguments.
     * 
     * @param string $function Lua function name to call
     * @param array $arguments Arguments to pass to the function
     * @param int $useSelf Whether to use self context
     * @return mixed Returns the result of the called function or null on failure
     */
    public function call(string $function, array $arguments = [], int $useSelf = 0)
    {
        try {
            $result = $this->lua->call($function, $arguments, $useSelf);
            $this->lastError = null;
            return $result;
        } catch (\LuaException $e) {
            $this->lastError = $e->getMessage();
            $this->logError('Lua call error', [
                'message' => $e->getMessage(),
                'function' => $function,
                'arguments' => $arguments,
            ]);
            return null;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logError('Error calling Lua function', [
                'message' => $e->getMessage(),
                'function' => $function,
                'arguments' => $arguments,
            ]);
            return null;
        }
    }
}


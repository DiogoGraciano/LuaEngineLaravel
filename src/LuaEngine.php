<?php

namespace DiogoGraciano\LuaEngine;

use Exception;
use LuaSandbox;
use LuaSandboxError;
use LuaSandboxFunction;
use LuaSandboxMemoryError;
use LuaSandboxSyntaxError;
use LuaSandboxRuntimeError;
use LuaSandboxTimeoutError;
use DiogoGraciano\LuaEngine\Contracts\LuaEngineInterface;
use DiogoGraciano\LuaEngine\Utils\Utils;

/**
 * Lua Engine - Safe Lua script executor using LuaSandbox
 * 
 * @package DiogoGraciano\LuaEngine
 */
class LuaEngine implements LuaEngineInterface
{
    protected LuaSandbox $sandbox;
    protected array $optionsConfig;
    protected $lastError = null;
    protected array $registeredFunctions = [];

    public function __construct(array $config = [])
    {
        // Check if LuaSandbox extension is installed
        if (!extension_loaded('luasandbox')) {
            throw new Exception('LuaSandbox extension is not installed. Run: sudo pecl install luasandbox');
        }

        $this->sandbox = new LuaSandbox();
        $this->optionsConfig = $config['options'] ?? [];
        $this->setupResourceLimits();
    }

    /**
     * Setup resource limits for the sandbox
     */
    protected function setupResourceLimits(): void
    {
        // Set memory limit (in bytes)
        $memoryLimit = $this->optionsConfig['memory_limit'] ?? '50M';
        $memoryLimitBytes = Utils::parseMemoryLimit($memoryLimit);
        $this->sandbox->setMemoryLimit($memoryLimitBytes);

        // Set CPU limit (in seconds)
        $cpuLimit = $this->optionsConfig['cpu_limit'];
        if (is_numeric($cpuLimit) && $cpuLimit > 0) {
            $this->sandbox->setCPULimit((float) $cpuLimit);
        }
    }

    /**
     * Execute Lua script with data
     * 
     * @param string $script Lua code
     * @param array $data Data available in the script
     * @return mixed Execution result
     */
    public function execute(string $script): mixed
    {
        try {
            // Load and execute script
            $function = $this->sandbox->loadString($script);
            $result = $function->call();
            
            // Clear previous error
            $this->lastError = null;
            
            // LuaSandbox returns arrays - return first element if single result
            if (is_array($result) && count($result) === 1) {
                return $result[0];
            }
            
            return $result;
            
        } catch (LuaSandboxSyntaxError $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Lua syntax error', [
                'message' => $e->getMessage(),
                'script' => $script,
            ], $this->optionsConfig);
            throw new Exception("Lua syntax error: " . $e->getMessage());
        } catch (LuaSandboxRuntimeError $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Lua runtime error', [
                'message' => $e->getMessage(),
                'script' => $script,
            ], $this->optionsConfig);
            throw new Exception("Lua runtime error: " . $e->getMessage());
        } catch (LuaSandboxTimeoutError $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Lua timeout error', [
                'message' => $e->getMessage(),
                'script' => $script,
            ], $this->optionsConfig);
            throw new Exception("Lua execution timeout: " . $e->getMessage());
        } catch (LuaSandboxMemoryError $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Lua memory error', [
                'message' => $e->getMessage(),
                'script' => $script,
            ], $this->optionsConfig);
            throw new Exception("Lua memory limit exceeded: " . $e->getMessage());
        } catch (LuaSandboxError $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Lua execution error', [
                'message' => $e->getMessage(),
                'script' => $script,
            ], $this->optionsConfig);
            throw new Exception("Lua script error: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Error executing Lua script', [
                'message' => $e->getMessage(),
                'script' => $script,
            ], $this->optionsConfig);
            throw new Exception("Error executing Lua script: " . $e->getMessage());
        }
    }

    /**
     * Execute a Lua trigger and return true/false
     * 
     * @param string $script
     * @return bool
     */
    public function evaluate(string $script): bool
    {
        try {
            // For triggers, we expect to return true/false
            $wrappedScript = "return (" . $script . ")";
            
            // Load and execute script
            $function = $this->sandbox->loadString($wrappedScript);
            $result = $function->call();
            
            $this->lastError = null;
            
            // Get first result if array
            $value = is_array($result) && count($result) > 0 ? $result[0] : $result;
            return (bool) $value;
            
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Error evaluating Lua trigger: ' . $e->getMessage(), [], $this->optionsConfig);
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
            // Try to load the script (compiles it)
            $this->sandbox->loadString($script);
            return true;
        } catch (LuaSandboxSyntaxError $e) {
            Utils::logError('Invalid Lua script', [
                'error' => $e->getMessage(),
                'script' => $script,
            ], $this->optionsConfig);
            return false;
        } catch (\Exception $e) {
            Utils::logError('Error validating Lua script', [
                'error' => $e->getMessage(),
                'script' => $script,
            ], $this->optionsConfig);
            return false;
        }
    }

    /**
     * Register a set of PHP functions as a Lua library
     * 
     * This method wraps LuaSandbox::registerLibrary(). If a library with the same name
     * already exists, the new functions will be added to the existing table (not replaced).
     * 
     * @param string $libName The name of the library. In the Lua state, the global 
     *                        variable of this name will be set to a table of functions.
     * @param array $functions An associative array where each key is a function name 
     *                        and each value is a corresponding PHP callable (function 
     *                        name string or callable).
     * 
     * @return void
     * 
     * @see https://www.php.net/manual/en/luasandbox.registerlibrary.php
     * 
     * @note Functions that return values must return arrays. The first element of the
     *       returned array will be used as the return value in Lua. Functions that don't
     *       need to return values can return nothing (void).
     * 
     * @example
     * // Define a PHP function
     * function frobnosticate($v) {
     *     return [$v + 42]; // Return array for values
     * }
     * 
     * $engine->registerLibrary('php', [
     *     'frobnosticate_in_lua' => 'frobnosticate', // Function name as string or callable
     *     'output' => function($string) {
     *         echo "$string\n"; // No return needed for void functions
     *     },
     *     'error' => function() {
     *         throw new \LuaSandboxRuntimeError("Something is wrong");
     *     },
     * ]);
     */
    public function registerLibrary(array $functions, string $libName = 'php'): void
    {
        try {
            if (isset($this->registeredFunctions[$libName])) {
                $this->registeredFunctions[$libName] = array_merge(
                    $this->registeredFunctions[$libName],
                    $functions
                );
            } else {
                $this->registeredFunctions[$libName] = $functions;
            }
            
            $this->sandbox->registerLibrary($libName, $functions);
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Utils::logError("Error registering Lua library '{$libName}': " . $e->getMessage(), [], $this->optionsConfig);;
            throw $e;
        }
    }

    /**
     * Register a PHP function in a library in Lua
     * 
     * @param string $functionName Function name
     * @param callable $function Function to register
     * @param string $libName Library name
     * @return bool True if function is registered, false otherwise
     */
    public function registerFunction(string $functionName, callable $function, string $libName = 'php'): void
    {
        try {
            $this->registeredFunctions[$libName][$functionName] = $function;
            $this->sandbox->registerLibrary($libName, [$functionName => $function]);
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Utils::logError("Error registering Lua function '{$functionName}' in library '{$libName}': " . $e->getMessage(), [], $this->optionsConfig);
            throw $e;
        }
    }

     /**
     * Get the registered functions
     * 
     * @param string $libName Library name
     * @return array Registered functions
    */
    public function getRegisteredLibrary(): array
    {
        return $this->registeredFunctions;
    }

    /**
     * Get the registered functions
     * 
     * @param string $libName Library name
     * @return array Registered functions
     */
    public function getRegisteredFunctions(string $libName = 'php'): array
    {
        return $this->registeredFunctions[$libName] ?? [];
    }

    /**
     * Get the registered function
     * 
     * @param string $functionName Function name
     * @param string $libName Library name
     * @return bool True if function is registered, false otherwise
     */
    public function isRegisteredFunction(string $functionName, string $libName = 'php'): bool
    {
        return isset($this->registeredFunctions[$libName][$functionName]);
    }

    /**
     * Load a Lua string into a function (LuaSandbox::loadString equivalent)
     * 
     * @param string $code Lua code string
     * @return LuaSandboxFunction|false
     */
    public function loadString(string $code): LuaSandboxFunction|false
    {
        try {
            $this->lastError = null;
            return $this->sandbox->loadString($code);
        } catch (LuaSandboxSyntaxError $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Lua syntax error in loadString', [
                'message' => $e->getMessage(),
                'code' => $code,
            ], $this->optionsConfig);
            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Error loading Lua string', [
                'message' => $e->getMessage(),
            ], $this->optionsConfig);
            return false;
        }
    }

    /**
     * Call a function in a Lua global variable (LuaSandbox::callFunction equivalent)
     * 
     * @param string $name Lua function name
     * @param mixed ...$args Variable arguments
     * @return array|false
     */
    public function callFunction(string $name, mixed ...$args): array|false
    {
        try {
            $this->lastError = null;
            return $this->sandbox->callFunction($name, ...$args);
        } catch (LuaSandboxError $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Lua callFunction error', [
                'message' => $e->getMessage(),
                'function' => $name,
            ], $this->optionsConfig);
            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Error calling Lua function', [
                'message' => $e->getMessage(),
                'function' => $name,
            ], $this->optionsConfig);
            return false;
        }
    }

    /**
     * Wrap a PHP function/callable for use in Lua (LuaSandbox::wrapPhpFunction equivalent)
     * 
     * @param callable $function PHP callable to wrap
     * @return LuaSandboxFunction|false
     */
    public function wrapPhpFunction(callable $function): LuaSandboxFunction|false
    {
        try {
            $this->lastError = null;
            return $this->sandbox->wrapPhpFunction($function);
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Utils::logError('Error wrapping PHP function', [
                'message' => $e->getMessage(),
            ], $this->optionsConfig);
            return false;
        }
    }

    /**
     * Set memory limit for the sandbox (LuaSandbox::setMemoryLimit equivalent)
     * 
     * @param int $bytes Memory limit in bytes
     * @return void
     */
    public function setMemoryLimit(int $bytes): void
    {
        $this->sandbox->setMemoryLimit($bytes);
    }

    /**
     * Set CPU time limit for the sandbox (LuaSandbox::setCPULimit equivalent)
     * 
     * @param float $seconds CPU limit in seconds
     * @return void
     */
    public function setCPULimit(float $seconds): void
    {
        $this->sandbox->setCPULimit($seconds);
    }

    /**
     * Get the underlying LuaSandbox instance
     * 
     * @return LuaSandbox|null
     */
    public function getSandbox(): ?LuaSandbox
    {
        return $this->sandbox;
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
        return extension_loaded('luasandbox');
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
        return $this->execute($script);
    }
}


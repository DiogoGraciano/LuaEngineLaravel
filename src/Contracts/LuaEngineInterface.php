<?php

namespace DiogoGraciano\LuaEngine\Contracts;

use LuaSandboxFunction;

/**
 * Interface for Lua Engine - Aligned with LuaSandbox API
 * 
 * This interface provides methods aligned with LuaSandbox while maintaining
 * additional convenience methods for common use cases.
 */
interface LuaEngineInterface
{
    /**
     * Load a Lua string into a function (LuaSandbox::loadString equivalent)
     * 
     * @param string $code Lua code string
     * @return LuaSandboxFunction|false Returns function on success, false on failure
     */
    public function loadString(string $code): LuaSandboxFunction|false;

    /**
     * Call a function in a Lua global variable (LuaSandbox::callFunction equivalent)
     * 
     * The function name can contain "." for nested table access (e.g., "string.match")
     * 
     * @param string $name Lua function name
     * @param mixed ...$args Variable arguments to pass to the function
     * @return array|false Returns array of values or false on failure
     */
    public function callFunction(string $name, mixed ...$args): array|false;

    /**
     * Register a PHP library in Lua (LuaSandbox::registerLibrary equivalent)
     * 
     * @param string $libName Library name
     * @param array $functions Associative array of function names to callables
     * @return void
     */
    public function registerLibrary(string $libName, array $functions): void;

    /**
     * Wrap a PHP function/callable for use in Lua (LuaSandbox::wrapPhpFunction equivalent)
     * 
     * @param callable $function PHP callable to wrap
     * @return \LuaSandboxFunction|false Returns wrapped function or false on failure
     */
    public function wrapPhpFunction(callable $function): \LuaSandboxFunction|false;

    /**
     * Set memory limit for the sandbox (LuaSandbox::setMemoryLimit equivalent)
     * 
     * @param int $bytes Memory limit in bytes
     * @return void
     */
    public function setMemoryLimit(int $bytes): void;

    /**
     * Set CPU time limit for the sandbox (LuaSandbox::setCPULimit equivalent)
     * 
     * @param float $seconds CPU limit in seconds
     * @return void
     */
    public function setCPULimit(float $seconds): void;

    /**
     * Execute Lua script with data
     * 
     * Convenience method that loads and executes code with data binding
     * 
     * @param string $script Lua code
     * @param array $data Data available as 'data' variable in Lua
     * @return mixed Execution result
     */
    public function execute(string $script): mixed;

    /**
     * Execute a Lua expression and return boolean result
     * 
     * Convenience method for conditional evaluations
     * 
     * @param string $script Lua expression
     * @param array $data Data available as 'data' variable in Lua
     * @return bool Evaluation result
     */
    public function evaluate(string $script): bool;

    /**
     * Validate script syntax without executing
     * 
     * @param string $script Lua code to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(string $script): bool;

    /**
     * Assign PHP variable to Lua environment
     * 
     * @param string $name Variable name in Lua
     * @param mixed $value Variable value
     * @return mixed Returns $this on success, null on failure
     */
    public function assign(string $name, mixed $value): mixed;

    /**
     * Get the last error message
     * 
     * @return string|null Last error message or null
     */
    public function getLastError(): ?string;

    /**
     * Test if LuaSandbox extension is available
     * 
     * @return bool True if extension is loaded
     */
    public static function isAvailable(): bool;

    /**
     * Get the underlying LuaSandbox instance
     * 
     * @return \LuaSandbox|null LuaSandbox instance
     */
    public function getSandbox(): ?\LuaSandbox;
}


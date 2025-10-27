<?php

namespace DiogoGraciano\LuaEngine\Contracts;

/**
 * Interface for Lua Engine
 */
interface LuaEngineInterface
{
    /**
     * Execute Lua script with data
     */
    public function execute(string $script, array $data = []);

    /**
     * Execute a Lua trigger and return true/false
     */
    public function evaluate(string $script, array $data = []): bool;

    /**
     * Validate script syntax without executing
     */
    public function validate(string $script): bool;

    /**
     * Register a PHP function that can be called from Lua
     */
    public function registerFunction(string $name, callable $callback): void;

    /**
     * Get the last error
     */
    public function getLastError(): ?string;

    /**
     * Test if extension is available
     */
    public static function isAvailable(): bool;

    /**
     * Assign PHP variable to Lua script
     * 
     * @param string $name Variable name in Lua
     * @param mixed $value Variable value
     * @return mixed Returns $this or null on failure
     */
    public function assign(string $name, $value);

    /**
     * Call Lua function
     * 
     * @param string $function Function name to call
     * @param array $arguments Arguments to pass to the function
     * @param int $useSelf Whether to use self context
     * @return mixed Returns function result or null on failure
     */
    public function call(string $function, array $arguments = [], int $useSelf = 0);
}


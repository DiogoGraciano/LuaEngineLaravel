<?php

namespace DiogoGraciano\LuaEngine\Facades;

use Illuminate\Support\Facades\Facade;
use DiogoGraciano\LuaEngine\Contracts\LuaEngineInterface;

/**
 * Facade for Lua Engine
 * 
 * @method static mixed execute(string $script)
 * @method static bool evaluate(string $script)
 * @method static bool validate(string $script)
 * @method static void registerLibrary(array $functions, string $libName = 'php')
 * @method static void registerFunction(string $functionName, callable $function, string $libName = 'php')
 * @method static array getRegisteredLibrary()
 * @method static array getRegisteredFunctions(string $libName = 'php')
 * @method static bool isRegisteredFunction(string $functionName, string $libName = 'php')
 * @method static \LuaSandboxFunction|false loadString(string $code)
 * @method static array|false callFunction(string $name, mixed ...$args)
 * @method static \LuaSandboxFunction|false wrapPhpFunction(callable $function)
 * @method static void setMemoryLimit(int $bytes)
 * @method static void setCPULimit(float $seconds)
 * @method static ?\LuaSandbox getSandbox()
 * @method static string|null getLastError()
 * @method static bool isAvailable()
 * @method static mixed executeFile(string $filePath, array $data = [])
 * 
 * @see \DiogoGraciano\LuaEngine\LuaEngine
 */
class LuaEngine extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return LuaEngineInterface::class;
    }
}


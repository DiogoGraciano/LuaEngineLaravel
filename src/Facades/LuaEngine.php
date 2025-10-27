<?php

namespace DiogoGraciano\LuaEngine\Facades;

use Illuminate\Support\Facades\Facade;
use DiogoGraciano\LuaEngine\Contracts\LuaEngineInterface;

/**
 * Facade for Lua Engine
 * 
 * @method static mixed execute(string $script, array $data = [])
 * @method static bool evaluate(string $script, array $data = [])
 * @method static bool validate(string $script)
 * @method static void registerFunction(string $name, callable $callback)
 * @method static string|null getLastError()
 * @method static bool isAvailable()
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


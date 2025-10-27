<?php

namespace DiogoGraciano\LuaEngine;

use Illuminate\Support\ServiceProvider;
use DiogoGraciano\LuaEngine\Contracts\LuaEngineInterface;

/**
 * Service Provider for Lua Engine
 */
class LuaEngineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/lua-engine.php',
            'lua-engine'
        );

        // Register LuaEngine singleton
        $this->app->singleton(LuaEngineInterface::class, function ($app) {
            $config = $app['config']->get('lua-engine');
            return new LuaEngine($config);
        });

        // Register alias
        $this->app->alias(LuaEngineInterface::class, 'lua.engine');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/lua-engine.php' => config_path('lua-engine.php'),
        ], 'lua-engine-config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [LuaEngineInterface::class, 'lua.engine'];
    }
}


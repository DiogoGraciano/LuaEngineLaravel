<?php

namespace DiogoGraciano\LuaEngine\Tests;

use DiogoGraciano\LuaEngine\Utils\Utils;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use Mockery;

/**
 * Unit tests for Utils class
 * 
 * @package DiogoGraciano\LuaEngine\Tests
 */
class UtilsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            \DiogoGraciano\LuaEngine\LuaEngineServiceProvider::class,
        ];
    }

    /**
     * Test parseMemoryLimit with bytes (numeric value)
     */
    public function test_parseMemoryLimit_with_bytes()
    {
        $result = Utils::parseMemoryLimit(1024);
        $this->assertEquals(1024, $result);
    }

    /**
     * Test parseMemoryLimit with kilobytes
     */
    public function test_parseMemoryLimit_with_kilobytes()
    {
        $result = Utils::parseMemoryLimit('64K');
        $this->assertEquals(64 * 1024, $result);
        
        $result = Utils::parseMemoryLimit('128k');
        $this->assertEquals(128 * 1024, $result);
    }

    /**
     * Test parseMemoryLimit with megabytes
     */
    public function test_parseMemoryLimit_with_megabytes()
    {
        $result = Utils::parseMemoryLimit('64M');
        $this->assertEquals(64 * 1024 * 1024, $result);
        
        $result = Utils::parseMemoryLimit('128m');
        $this->assertEquals(128 * 1024 * 1024, $result);
    }

    /**
     * Test parseMemoryLimit with gigabytes
     */
    public function test_parseMemoryLimit_with_gigabytes()
    {
        $result = Utils::parseMemoryLimit('2G');
        $this->assertEquals(2 * 1024 * 1024 * 1024, $result);
        
        $result = Utils::parseMemoryLimit('4g');
        $this->assertEquals(4 * 1024 * 1024 * 1024, $result);
    }

    /**
     * Test parseMemoryLimit with invalid format (defaults to int cast)
     */
    public function test_parseMemoryLimit_with_invalid_format()
    {
        $result = Utils::parseMemoryLimit('invalid');
        $this->assertEquals(0, $result); // (int)'invalid' = 0
    }

    /**
     * Test parseMemoryLimit with string containing numbers only
     */
    public function test_parseMemoryLimit_with_numeric_string()
    {
        $result = Utils::parseMemoryLimit('1024');
        $this->assertEquals(1024, $result);
    }

    /**
     * Test parseMemoryLimit with whitespace
     */
    public function test_parseMemoryLimit_with_whitespace()
    {
        $result = Utils::parseMemoryLimit(' 64M ');
        $this->assertEquals(64 * 1024 * 1024, $result);
    }

    /**
     * Test logError with default configuration
     */
    public function test_logError_with_default_config()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Test message', []);
        
        Utils::logError('Test message', [], []);
    }

    /**
     * Test logError with debug level
     */
    public function test_logError_with_debug_level()
    {
        Log::shouldReceive('debug')
            ->once()
            ->with('Debug message', ['key' => 'value']);
        
        Utils::logError('Debug message', ['key' => 'value'], [
            'log_level' => 'debug'
        ]);
    }

    /**
     * Test logError with info level
     */
    public function test_logError_with_info_level()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Info message', []);
        
        Utils::logError('Info message', [], [
            'log_level' => 'info'
        ]);
    }

    /**
     * Test logError with warning level
     */
    public function test_logError_with_warning_level()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Warning message', []);
        
        Utils::logError('Warning message', [], [
            'log_level' => 'warning'
        ]);
    }

    /**
     * Test logError with error level
     */
    public function test_logError_with_error_level()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Error message', []);
        
        Utils::logError('Error message', [], [
            'log_level' => 'error'
        ]);
    }

    /**
     * Test logError with disabled logging
     */
    public function test_logError_disabled()
    {
        Log::shouldNotReceive('error');
        Log::shouldNotReceive('debug');
        Log::shouldNotReceive('info');
        Log::shouldNotReceive('warning');
        
        Utils::logError('Should not log', [], [
            'log_errors' => false
        ]);
    }

    /**
     * Test logError with array message (converts to default message)
     */
    public function test_logError_with_array_message()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Lua error', ['error' => 'test error']);
        
        Utils::logError(['error' => 'test error'], [], []);
    }

    /**
     * Test logError with empty context
     */
    public function test_logError_with_empty_context()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Test', []);
        
        Utils::logError('Test', [], []);
    }

    /**
     * Test logError with complex context
     */
    public function test_logError_with_complex_context()
    {
        $context = [
            'message' => 'Error details',
            'script' => 'return true',
            'line' => 42,
        ];
        
        Log::shouldReceive('error')
            ->once()
            ->with('Lua error occurred', $context);
        
        Utils::logError('Lua error occurred', $context, []);
    }
}


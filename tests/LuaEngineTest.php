<?php

namespace DiogoGraciano\LuaEngine\Tests;

use DiogoGraciano\LuaEngine\LuaEngine;
use Exception;
use Orchestra\Testbench\TestCase;
use Mockery;

/**
 * Unit tests for LuaEngine class
 * 
 * Note: These tests require the Lua extension to be installed.
 * Run: sudo pecl install lua
 */
class LuaEngineTest extends TestCase
{
    protected LuaEngine $luaEngine;
    protected array $defaultConfig = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip tests if Lua extension is not available
        if (!extension_loaded('lua')) {
            $this->markTestSkipped('Lua extension is not installed. Run: sudo pecl install lua');
        }
        
        $this->defaultConfig = [
            'sandbox' => [
                'allow_math' => true,
                'allow_string' => true,
                'allow_table' => true,
                'global_functions' => [],
            ],
            'options' => [
                'timeout' => 30,
                'memory_limit' => '64M',
                'log_errors' => true,
                'log_level' => 'error',
            ],
        ];
    }

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
     * Test constructor with default configuration
     */
    public function test_constructor_with_default_config()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $this->assertInstanceOf(LuaEngine::class, $engine);
    }

    /**
     * Test constructor throws exception when Lua extension is not loaded
     */
    public function test_constructor_throws_exception_when_lua_not_installed()
    {
        // Temporarily simulate missing extension
        if (extension_loaded('lua')) {
            $this->markTestSkipped('Lua extension is installed, cannot test this scenario');
        }
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Lua extension is not installed');
        
        new LuaEngine($this->defaultConfig);
    }

    /**
     * Test constructor with custom sandbox configuration
     */
    public function test_constructor_with_custom_sandbox_config()
    {
        $config = [
            'sandbox' => [
                'allow_math' => false,
                'allow_string' => false,
                'allow_table' => false,
            ],
        ];
        
        $engine = new LuaEngine($config);
        $this->assertInstanceOf(LuaEngine::class, $engine);
    }

    /**
     * Test setupSandbox with all modules disabled
     */
    public function test_setupSandbox_with_disabled_modules()
    {
        $config = [
            'sandbox' => [
                'allow_math' => false,
                'allow_string' => false,
                'allow_table' => false,
            ],
        ];
        
        $engine = new LuaEngine($config);
        $this->assertInstanceOf(LuaEngine::class, $engine);
    }

    /**
     * Test execute with simple script
     */
    public function test_execute_simple_script()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $result = $engine->execute('return 42');
        $this->assertEquals(42, $result);
    }

    /**
     * Test execute with data variable
     */
    public function test_execute_with_data_variable()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $data = ['name' => 'Test', 'value' => 100];
        $result = $engine->execute('return data.value', $data);
        $this->assertEquals(100, $result);
    }

    /**
     * Test execute with math operations
     */
    public function test_execute_with_math_operations()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $result = $engine->execute('return math.floor(3.7)');
        $this->assertEquals(3, $result);
    }

    /**
     * Test execute with string operations
     */
    public function test_execute_with_string_operations()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $result = $engine->execute('return string.upper("hello")');
        $this->assertEquals('HELLO', $result);
    }

    /**
     * Test execute with table operations
     */
    public function test_execute_with_table_operations()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $data = ['items' => [1, 2, 3]];
        $result = $engine->execute('return #data.items', $data);
        $this->assertEquals(3, $result);
    }

    /**
     * Test execute throws exception on invalid Lua code
     */
    public function test_execute_throws_exception_on_invalid_code()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $this->expectException(Exception::class);
        
        $engine->execute('invalid lua syntax [[[[');
    }

    /**
     * Test evaluate returns true for valid condition
     */
    public function test_evaluate_returns_true()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $result = $engine->evaluate('data.value > 50', ['value' => 100]);
        $this->assertTrue($result);
    }

    /**
     * Test evaluate returns false for invalid condition
     */
    public function test_evaluate_returns_false()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $result = $engine->evaluate('data.value > 50', ['value' => 10]);
        $this->assertFalse($result);
    }

    /**
     * Test evaluate catches exception and returns false
     */
    public function test_evaluate_catches_exception_and_returns_false()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $result = $engine->evaluate('invalid lua syntax [[[[');
        $this->assertFalse($result);
        $this->assertNotNull($engine->getLastError());
    }

    /**
     * Test validate returns true for valid syntax
     */
    public function test_validate_returns_true_for_valid_syntax()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $result = $engine->validate('return true');
        $this->assertTrue($result);
    }

    /**
     * Test validate returns false for invalid syntax
     */
    public function test_validate_returns_false_for_invalid_syntax()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $result = $engine->validate('invalid syntax [[[[');
        $this->assertFalse($result);
    }

    /**
     * Test registerFunction with PHP function
     */
    public function test_registerFunction_with_php_function()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $callback = function($x) {
            return $x * 2;
        };
        
        $engine->registerFunction('double', $callback);
        
        $result = $engine->execute('return double(21)');
        $this->assertEquals(42, $result);
    }

    /**
     * Test getLastError returns null initially
     */
    public function test_getLastError_returns_null_initially()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $this->assertNull($engine->getLastError());
    }

    /**
     * Test getLastError returns error after execution failure
     */
    public function test_getLastError_returns_error_after_failure()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        try {
            $engine->execute('invalid syntax [[[[');
        } catch (Exception $e) {
            // Expected
        }
        
        $this->assertNotNull($engine->getLastError());
    }

    /**
     * Test getLastError is cleared after successful execution
     */
    public function test_getLastError_cleared_after_success()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        // Cause an error
        try {
            $engine->execute('invalid syntax [[[[');
        } catch (Exception $e) {
            // Expected
        }
        
        $this->assertNotNull($engine->getLastError());
        
        // Successful execution
        $engine->execute('return 42');
        $this->assertNull($engine->getLastError());
    }

    /**
     * Test isAvailable returns true when extension is loaded
     */
    public function test_isAvailable_returns_true_when_extension_loaded()
    {
        if (extension_loaded('lua')) {
            $this->assertTrue(LuaEngine::isAvailable());
        } else {
            $this->markTestSkipped('Cannot test - Lua extension not installed');
        }
    }

    /**
     * Test executeFile with valid file
     */
    public function test_executeFile_with_valid_file()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        // Create temporary Lua file
        $tempFile = tempnam(sys_get_temp_dir(), 'lua_test') . '.lua';
        file_put_contents($tempFile, 'return 42');
        
        try {
            $result = $engine->executeFile($tempFile);
            $this->assertEquals(42, $result);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test executeFile throws exception when file not found
     */
    public function test_executeFile_throws_exception_when_file_not_found()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Lua file not found');
        
        $engine->executeFile('/nonexistent/file.lua');
    }

    /**
     * Test registerFunctions with multiple functions
     */
    public function test_registerFunctions_with_multiple_functions()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $functions = [
            'square' => function($x) { return $x * $x; },
            'cube' => function($x) { return $x * $x * $x; },
        ];
        
        $engine->registerFunctions($functions);
        
        $result1 = $engine->execute('return square(5)');
        $this->assertEquals(25, $result1);
        
        $result2 = $engine->execute('return cube(3)');
        $this->assertEquals(27, $result2);
    }

    /**
     * Test clearGlobals
     */
    public function test_clearGlobals()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        // Assign data
        $engine->execute('data = {value = 42}');
        
        // Clear globals
        $engine->clearGlobals();
        
        // Try to access data (should be nil)
        $result = $engine->execute('return data');
        $this->assertNull($result);
    }

    /**
     * Test assign with different data types
     */
    public function test_assign_with_different_data_types()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        // Test with string
        $result = $engine->assign('testString', 'hello');
        $this->assertNotNull($result);
        
        // Test with number
        $result = $engine->assign('testNumber', 42);
        $this->assertNotNull($result);
        
        // Test with array
        $result = $engine->assign('testArray', [1, 2, 3]);
        $this->assertNotNull($result);
        
        // Test with boolean
        $result = $engine->assign('testBool', true);
        $this->assertNotNull($result);
    }

    /**
     * Test assign and verify value in Lua
     */
    public function test_assign_and_verify_value()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $engine->assign('myVar', 'test_value');
        $result = $engine->execute('return myVar');
        
        $this->assertEquals('test_value', $result);
    }

    /**
     * Test call with valid Lua function
     */
    public function test_call_with_valid_function()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        // Define function in Lua
        $engine->execute('
            function add(a, b)
                return a + b
            end
        ');
        
        // Call function
        $result = $engine->call('add', [5, 3]);
        $this->assertEquals(8, $result);
    }

    /**
     * Test call with nonexistent function
     */
    public function test_call_with_nonexistent_function()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        // Call function that doesn't exist
        $result = $engine->call('nonexistent', []);
        $this->assertNull($result);
        $this->assertNotNull($engine->getLastError());
    }

    /**
     * Test applyResourceLimits with timeout
     */
    public function test_applyResourceLimits_with_timeout()
    {
        $config = [
            'options' => [
                'timeout' => 60,
            ],
        ];
        
        $engine = new LuaEngine($config);
        $this->assertInstanceOf(LuaEngine::class, $engine);
    }

    /**
     * Test parseMemoryLimit with different formats
     */
    public function test_parseMemoryLimit_different_formats()
    {
        // We can't easily test this protected method, but we can verify behavior indirectly
        $config = [
            'options' => [
                'memory_limit' => '128M',
            ],
        ];
        
        $engine = new LuaEngine($config);
        $this->assertInstanceOf(LuaEngine::class, $engine);
    }

    /**
     * Test logError with different log levels
     */
    public function test_logError_with_debug_level()
    {
        $config = [
            'options' => [
                'log_errors' => true,
                'log_level' => 'debug',
            ],
        ];
        
        $engine = new LuaEngine($config);
        $engine->execute('return true'); // No error to log
        
        $this->assertInstanceOf(LuaEngine::class, $engine);
    }

    /**
     * Test logError with info level
     */
    public function test_logError_with_info_level()
    {
        $config = [
            'options' => [
                'log_errors' => true,
                'log_level' => 'info',
            ],
        ];
        
        $engine = new LuaEngine($config);
        $this->assertInstanceOf(LuaEngine::class, $engine);
    }

    /**
     * Test logError with warning level
     */
    public function test_logError_with_warning_level()
    {
        $config = [
            'options' => [
                'log_errors' => true,
                'log_level' => 'warning',
            ],
        ];
        
        $engine = new LuaEngine($config);
        $this->assertInstanceOf(LuaEngine::class, $engine);
    }

    /**
     * Test logError disabled
     */
    public function test_logError_disabled()
    {
        $config = [
            'options' => [
                'log_errors' => false,
            ],
        ];
        
        $engine = new LuaEngine($config);
        
        // Try to cause an error
        try {
            $engine->execute('invalid syntax [[[[');
        } catch (Exception $e) {
            // Error should not be logged
        }
        
        $this->assertInstanceOf(LuaEngine::class, $engine);
    }

    /**
     * Test global_functions configuration
     */
    public function test_global_functions_configuration()
    {
        $config = [
            'sandbox' => [
                'global_functions' => [
                    'testFunc' => function($x) {
                        return $x * 2;
                    },
                ],
            ],
        ];
        
        $engine = new LuaEngine($config);
        
        $result = $engine->execute('return testFunc(21)');
        $this->assertEquals(42, $result);
    }

    /**
     * Test execute with complex data structure
     */
    public function test_execute_with_complex_data_structure()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $data = [
            'user' => [
                'name' => 'John',
                'age' => 30,
                'hobbies' => ['reading', 'coding'],
            ],
        ];
        
        $result = $engine->execute('return data.user.name', $data);
        $this->assertEquals('John', $result);
        
        // Lua arrays start at 1, so hobbies[1] is the first element
        $result = $engine->execute('return data.user.hobbies[1]', $data);
        $this->assertEquals('reading', $result);
    }

    /**
     * Test evaluate with complex condition
     */
    public function test_evaluate_with_complex_condition()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $data = [
            'user' => ['age' => 25],
            'minimumAge' => 18,
        ];
        
        $result = $engine->evaluate('data.user.age >= data.minimumAge', $data);
        $this->assertTrue($result);
    }

    /**
     * Test execute with multiple variables
     */
    public function test_execute_with_multiple_variables()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $engine->assign('var1', 10);
        $engine->assign('var2', 20);
        
        $result = $engine->execute('return var1 + var2');
        $this->assertEquals(30, $result);
    }

    /**
     * Test execute with table access
     */
    public function test_execute_with_table_access()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $data = [
            'numbers' => [10, 20, 30],
        ];
        
        $result = $engine->execute('return data.numbers[1]', $data);
        $this->assertEquals(10, $result);
        
        $result = $engine->execute('return data.numbers[2]', $data);
        $this->assertEquals(20, $result);
    }

    /**
     * Test evaluate returns true for complex expression
     */
    public function test_evaluate_complex_expression_true()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $data = [
            'status' => 'active',
            'score' => 85,
        ];
        
        $result = $engine->evaluate(
            'data.status == "active" and data.score > 80',
            $data
        );
        
        $this->assertTrue($result);
    }

    /**
     * Test evaluate returns false for complex expression
     */
    public function test_evaluate_complex_expression_false()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $data = [
            'status' => 'inactive',
            'score' => 85,
        ];
        
        $result = $engine->evaluate(
            'data.status == "active" and data.score > 80',
            $data
        );
        
        $this->assertFalse($result);
    }
}


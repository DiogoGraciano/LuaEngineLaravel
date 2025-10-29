<?php

namespace DiogoGraciano\LuaEngine\Tests;

use DiogoGraciano\LuaEngine\LuaEngine;
use Exception;
use Orchestra\Testbench\TestCase;
use Mockery;

/**
 * Unit tests for LuaEngine class
 * 
 * Note: These tests require the LuaSandbox extension to be installed.
 * Run: sudo pecl install luasandbox
 */
class LuaEngineTest extends TestCase
{
    protected LuaEngine $luaEngine;
    protected array $defaultConfig = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip tests if LuaSandbox extension is not available
        if (!extension_loaded('luasandbox')) {
            $this->markTestSkipped('LuaSandbox extension is not installed. Run: sudo pecl install luasandbox');
        }
        
        $this->defaultConfig = [
            'options' => [
                'cpu_limit' => 30,
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
     * Test constructor throws exception when LuaSandbox extension is not loaded
     */
    public function test_constructor_throws_exception_when_luasandbox_not_installed()
    {
        // Temporarily simulate missing extension
        if (extension_loaded('luasandbox')) {
            $this->markTestSkipped('LuaSandbox extension is installed, cannot test this scenario');
        }
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('LuaSandbox extension is not installed');
        
        new LuaEngine($this->defaultConfig);
    }

    /**
     * Test constructor with custom configuration
     */
    public function test_constructor_with_custom_config()
    {
        $config = [
            'options' => [
                'cpu_limit' => 60,
                'memory_limit' => '128M',
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
     * Test execute with data variable using registerLibrary
     */
    public function test_execute_with_data_variable()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $data = ['name' => 'Test', 'value' => 100];
        
        // Register data as a library to make it available in Lua
        $engine->registerLibrary('data', [
            'getValue' => function() use ($data) {
                return $data['value'];
            },
            'getName' => function() use ($data) {
                return $data['name'];
            }
        ]);
        
        $result = $engine->execute('return data.getValue()');
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
        $items = [1, 2, 3];
        
        // Register items array as a library function
        $engine->registerLibrary('data', [
            'getItems' => function() use ($items) {
                return $items;
            },
            'getItemsCount' => function() use ($items) {
                return count($items);
            }
        ]);
        
        $result = $engine->execute('return data.getItemsCount()');
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
        $value = 100;
        
        $engine->registerLibrary('data', [
            'getValue' => function() use ($value) {
                return $value;
            }
        ]);
        
        $result = $engine->evaluate('data.getValue() > 50');
        $this->assertTrue($result);
    }

    /**
     * Test evaluate returns false for invalid condition
     */
    public function test_evaluate_returns_false()
    {
        $engine = new LuaEngine($this->defaultConfig);
        $value = 10;
        
        $engine->registerLibrary('data', [
            'getValue' => function() use ($value) {
                return $value;
            }
        ]);
        
        $result = $engine->evaluate('data.getValue() > 50');
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
     * Test registerLibrary with PHP function
     */
    public function test_registerLibrary_with_php_function()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $callback = function($x) {
            return $x * 2;
        };
        
        $engine->registerLibrary('math', [
            'double' => $callback
        ]);
        
        $result = $engine->execute('return math.double(21)');
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
        if (extension_loaded('luasandbox')) {
            $this->assertTrue(LuaEngine::isAvailable());
        } else {
            $this->markTestSkipped('Cannot test - LuaSandbox extension not installed');
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
     * Test registerLibrary with multiple functions
     */
    public function test_registerLibrary_with_multiple_functions()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $functions = [
            'square' => function($x) { return $x * $x; },
            'cube' => function($x) { return $x * $x * $x; },
        ];
        
        $engine->registerLibrary('math', $functions);
        
        $result1 = $engine->execute('return math.square(5)');
        $this->assertEquals(25, $result1);
        
        $result2 = $engine->execute('return math.cube(3)');
        $this->assertEquals(27, $result2);
    }

    /**
     * Test callFunction with valid Lua function
     */
    public function test_callFunction_with_valid_function()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        // Define function in Lua and store it globally
        $engine->execute('
            _G["add"] = function(a, b)
                return a + b
            end
        ');
        
        // Call function
        $result = $engine->callFunction('add', 5, 3);
        $this->assertIsArray($result);
        $this->assertEquals(8, $result[0]);
    }

    /**
     * Test callFunction with nonexistent function
     */
    public function test_callFunction_with_nonexistent_function()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        // Call function that doesn't exist
        $result = $engine->callFunction('nonexistent');
        $this->assertFalse($result);
        $this->assertNotNull($engine->getLastError());
    }

    /**
     * Test resource limits with cpu_limit
     */
    public function test_resource_limits_with_cpu_limit()
    {
        $config = [
            'options' => [
                'cpu_limit' => 60,
            ],
        ];
        
        $engine = new LuaEngine($config);
        $this->assertInstanceOf(LuaEngine::class, $engine);
    }

    /**
     * Test parseMemoryLimit with different formats
     * 
     * Note: This test now verifies the behavior indirectly through LuaEngine.
     * Direct unit tests for Utils::parseMemoryLimit can be found in UtilsTest.
     */
    public function test_parseMemoryLimit_different_formats()
    {
        // Test various memory limit formats through LuaEngine initialization
        $config = [
            'options' => [
                'memory_limit' => '128M',
            ],
        ];
        
        $engine = new LuaEngine($config);
        $this->assertInstanceOf(LuaEngine::class, $engine);
        
        // Test with kilobytes
        $config['options']['memory_limit'] = '64K';
        $engine2 = new LuaEngine($config);
        $this->assertInstanceOf(LuaEngine::class, $engine2);
        
        // Test with gigabytes
        $config['options']['memory_limit'] = '2G';
        $engine3 = new LuaEngine($config);
        $this->assertInstanceOf(LuaEngine::class, $engine3);
    }

    /**
     * Test logError with different log levels through LuaEngine
     * 
     * Note: Direct unit tests for Utils::logError can be found in UtilsTest.
     * These tests verify the integration with LuaEngine.
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
     * Test registerLibrary configuration
     */
    public function test_registerLibrary_configuration()
    {
        $config = [
            'options' => [
                'cpu_limit' => 30,
            ],
        ];
        
        $engine = new LuaEngine($config);
        
        $engine->registerLibrary('custom', [
            'testFunc' => function($x) {
                return $x * 2;
            },
        ]);
        
        $result = $engine->execute('return custom.testFunc(21)');
        $this->assertEquals(42, $result);
    }

    /**
     * Test execute with complex data structure using registerLibrary
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
        
        $engine->registerLibrary('data', [
            'getUserName' => function() use ($data) {
                return $data['user']['name'];
            },
            'getUserHobby' => function($index) use ($data) {
                return $data['user']['hobbies'][$index - 1]; // Lua is 1-indexed
            }
        ]);
        
        $result = $engine->execute('return data.getUserName()');
        $this->assertEquals('John', $result);
        
        // Lua arrays start at 1
        $result = $engine->execute('return data.getUserHobby(1)');
        $this->assertEquals('reading', $result);
    }

    /**
     * Test evaluate with complex condition
     */
    public function test_evaluate_with_complex_condition()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $userAge = 25;
        $minimumAge = 18;
        
        $engine->registerLibrary('data', [
            'getUserAge' => function() use ($userAge) {
                return $userAge;
            },
            'getMinimumAge' => function() use ($minimumAge) {
                return $minimumAge;
            }
        ]);
        
        $result = $engine->evaluate('data.getUserAge() >= data.getMinimumAge()');
        $this->assertTrue($result);
    }

    /**
     * Test execute with multiple variables using registerLibrary
     */
    public function test_execute_with_multiple_variables()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $var1 = 10;
        $var2 = 20;
        
        $engine->registerLibrary('vars', [
            'getVar1' => function() use ($var1) {
                return $var1;
            },
            'getVar2' => function() use ($var2) {
                return $var2;
            }
        ]);
        
        $result = $engine->execute('return vars.getVar1() + vars.getVar2()');
        $this->assertEquals(30, $result);
    }

    /**
     * Test execute with table access using registerLibrary
     */
    public function test_execute_with_table_access()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $numbers = [10, 20, 30];
        
        $engine->registerLibrary('data', [
            'getNumber' => function($index) use ($numbers) {
                return $numbers[$index - 1]; // Lua is 1-indexed
            }
        ]);
        
        $result = $engine->execute('return data.getNumber(1)');
        $this->assertEquals(10, $result);
        
        $result = $engine->execute('return data.getNumber(2)');
        $this->assertEquals(20, $result);
    }

    /**
     * Test evaluate returns true for complex expression
     */
    public function test_evaluate_complex_expression_true()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $status = 'active';
        $score = 85;
        
        $engine->registerLibrary('data', [
            'getStatus' => function() use ($status) {
                return $status;
            },
            'getScore' => function() use ($score) {
                return $score;
            }
        ]);
        
        $result = $engine->evaluate(
            'data.getStatus() == "active" and data.getScore() > 80'
        );
        
        $this->assertTrue($result);
    }

    /**
     * Test evaluate returns false for complex expression
     */
    public function test_evaluate_complex_expression_false()
    {
        $engine = new LuaEngine($this->defaultConfig);
        
        $status = 'inactive';
        $score = 85;
        
        $engine->registerLibrary('data', [
            'getStatus' => function() use ($status) {
                return $status;
            },
            'getScore' => function() use ($score) {
                return $score;
            }
        ]);
        
        $result = $engine->evaluate(
            'data.getStatus() == "active" and data.getScore() > 80'
        );
        
        $this->assertFalse($result);
    }
}


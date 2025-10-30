# Laravel Lua Engine

A secure Lua script execution engine for Laravel applications using the [PHP LuaSandbox extension](https://www.php.net/manual/en/class.luasandbox.php).

This package provides a clean, Laravel-friendly wrapper around LuaSandbox, making it easy to execute Lua scripts safely within your Laravel application with proper error handling, logging, and resource management.

## Features

- 🔒 **Safe Sandbox**: Automatic removal of dangerous Lua functions (os, io, require, etc.)
- ⚡ **Easy Integration**: Built specifically for Laravel
- 🎯 **Flexible Configuration**: Control which Lua modules are allowed
- 📊 **Data Binding**: Pass PHP variables to Lua scripts
- 🧪 **Resource Limits**: Timeout and memory limit controls
- 📝 **Error Logging**: Configurable logging levels
- ✅ **Validation**: Check script syntax before execution

## Requirements

- PHP 8.1 or higher (compatible with PHP 8.4)
- Laravel 10.0 or higher
- LuaSandbox PHP extension (`pecl install luasandbox`)

## Installation

1. Install the LuaSandbox PHP extension:
```bash
sudo pecl install luasandbox
```

2. Add this package to your Laravel application:
```bash
composer require diogo-graciano/laravel-lua-engine
```

3. Publish the configuration file:
```bash
php artisan vendor:publish --provider="DiogoGraciano\LuaEngine\LuaEngineServiceProvider"
```

## Configuration

The configuration file is published to `config/lua-engine.php`:

```php
return [
    'options' => [
        // CPU limit in seconds (0 = no limit)
        // Sets the maximum CPU time allowed for Lua execution
        // See: https://www.php.net/manual/en/luasandbox.setcpulimit.php
        'cpu_limit' => 30,
        
        // Maximum memory limit
        // Can be specified as:
        // - Integer bytes: 67108864
        // - String with unit: '64M', '128K', '2G'
        // See: https://www.php.net/manual/en/luasandbox.setmemorylimit.php
        'memory_limit' => '64M', // or 67108864 for 64MB
        
        // Log errors to Laravel log
        'log_errors' => true,
        
        // Log level for Lua errors
        // Available: 'error', 'warning', 'info', 'debug'
        'log_level' => 'error',
    ],
];
```

**Note:** LuaSandbox automatically provides a secure sandboxed environment. All dangerous Lua functions are disabled by default. See the [Security & Sandbox](#security--sandbox) section for details.

## Basic Usage

### Using the Facade

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Execute a simple Lua script
$result = LuaEngine::execute('return 42');
echo $result; // 42

// Pass data to Lua using registerLibrary
// Note: Functions must return arrays (LuaSandbox requirement)
$data = ['name' => 'John', 'age' => 30];
LuaEngine::registerLibrary([
    'getName' => function() use ($data) {
        return [$data['name']]; // Return as array
    },
    'getAge' => function() use ($data) {
        return [$data['age']];
    },
], 'data');
$result = LuaEngine::execute('return data.getName()');
echo $result; // John
```

### Dependency Injection

```php
use DiogoGraciano\LuaEngine\LuaEngine;

class MyController
{
    public function __construct(private LuaEngine $luaEngine) {}
    
    public function example()
    {
        $result = $this->luaEngine->execute('return math.floor(3.7)');
        return $result; // 3
    }
}
```

## Examples

### Example 1: Simple Calculations

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Basic math operations
$result = LuaEngine::execute('return 10 + 5 * 2');
// Result: 20

// Using Lua math library
$result = LuaEngine::execute('return math.sqrt(16)');
// Result: 4.0
```

### Example 2: Working with Data

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

$data = [
    'user' => [
        'name' => 'Alice',
        'age' => 28,
        'scores' => [85, 90, 78]
    ]
];

// Access nested data using registerLibrary
// Note: Functions must return arrays (LuaSandbox requirement)
LuaEngine::registerLibrary([
    'getUserName' => function() use ($data) {
        return [$data['user']['name']]; // Return as array
    },
    'getScore' => function($index) use ($data) {
        return [$data['user']['scores'][$index - 1]]; // Lua is 1-indexed
    },
], 'data');

$name = LuaEngine::execute('return data.getUserName()');
// Result: "Alice"

// Calculate average score
$avg = LuaEngine::execute(
    'return (data.getScore(1) + data.getScore(2) + data.getScore(3)) / 3'
);
// Result: 84.33333333333333
```

### Example 3: Conditional Evaluation

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Use evaluate() for boolean conditions
$data = ['age' => 25, 'minimumAge' => 18];

// Note: Functions must return arrays (LuaSandbox requirement)
LuaEngine::registerLibrary([
    'getAge' => function() use ($data) {
        return [$data['age']]; // Return as array
    },
    'getMinimumAge' => function() use ($data) {
        return [$data['minimumAge']];
    },
], 'data');

$canVote = LuaEngine::evaluate('data.getAge() >= data.getMinimumAge()');
// Result: true

$isAdult = LuaEngine::evaluate('data.getAge() >= 21');
// Result: true
```

### Example 4: String Operations

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

$data = ['message' => 'hello world'];

// Note: Functions must return arrays (LuaSandbox requirement)
LuaEngine::registerLibrary([
    'getMessage' => function() use ($data) {
        return [$data['message']]; // Return as array
    },
], 'data');

// Transform string
$upper = LuaEngine::execute('return string.upper(data.getMessage())');
// Result: "HELLO WORLD"

// Get string length
$length = LuaEngine::execute('return #data.getMessage()');
// Result: 11
```

### Example 5: Registering Custom PHP Functions

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Register a library of PHP functions
// Note: Functions must return arrays (LuaSandbox requirement)
LuaEngine::registerLibrary([
    'double' => function($x) {
        return [$x * 2]; // Return as array
    },
    'square' => function($x) {
        return [$x * $x];
    },
    'cube' => function($x) {
        return [$x * $x * $x];
    },
], 'custom');

// Use in Lua - functions are accessible as custom.functionName()
$result = LuaEngine::execute('return custom.double(21)');
// Result: 42

$square = LuaEngine::execute('return custom.square(5)'); // 25
$cube = LuaEngine::execute('return custom.cube(3)'); // 27

// Extend the library - add more functions to existing table
LuaEngine::registerLibrary([
    'add' => function($a, $b) {
        return [$a + $b];
    },
], 'custom');
// Now custom library has: double, square, cube, and add
```

### Example 6: Validating Script Syntax

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Check if script is valid before execution
$isValid = LuaEngine::validate('return true and false');
// Result: true

$isValid = LuaEngine::validate('invalid syntax [[[[');
// Result: false
```

### Example 7: Executing Lua Files

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Execute from a file
// Note: If your Lua script needs data, use registerLibrary first
// Functions must return arrays (LuaSandbox requirement)
LuaEngine::registerLibrary([
    'getValue' => function() {
        return [100]; // Return as array
    },
], 'data');
$result = LuaEngine::executeFile('/path/to/script.lua');
```

### Example 8: Calling Lua Functions

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Define a function in Lua and store it globally
LuaEngine::execute('
    _G["multiply"] = function(a, b)
        return a * b
    end
');

// Call the function using callFunction
$result = LuaEngine::callFunction('multiply', 6, 7);
// Result: [42] (returns array from LuaSandbox)

// Access first element if single result
$value = is_array($result) ? $result[0] : $result;
// Result: 42
```

### Example 9: Loading and Executing Lua Code Separately

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Load Lua code into a function
$func = LuaEngine::loadString('return a + b');

if ($func !== false) {
    // Execute the loaded function (note: you need to set variables first)
    // For simplicity, use execute() method instead
    $result = LuaEngine::execute('return 10 + 5');
    // Result: 15
}
```

### Example 10: Complex Business Logic

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

$order = [
    'items' => [
        ['price' => 10, 'quantity' => 2],
        ['price' => 15, 'quantity' => 1],
    ],
    'discount' => 0.1, // 10%
    'tax' => 0.08 // 8%
];

// Register order data as a library
// Note: Functions must return arrays (LuaSandbox requirement)
LuaEngine::registerLibrary([
    'getItemPrice' => function($index) use ($order) {
        return [$order['items'][$index - 1]['price']]; // Return as array
    },
    'getItemQuantity' => function($index) use ($order) {
        return [$order['items'][$index - 1]['quantity']];
    },
    'getItemsCount' => function() use ($order) {
        return [count($order['items'])];
    },
    'getDiscount' => function() use ($order) {
        return [$order['discount']];
    },
    'getTax' => function() use ($order) {
        return [$order['tax']];
    },
], 'data');

$total = LuaEngine::execute('
    local subtotal = 0
    local itemsCount = data.getItemsCount()
    for i = 1, itemsCount do
        subtotal = subtotal + data.getItemPrice(i) * data.getItemQuantity(i)
    end
    local discount = subtotal * data.getDiscount()
    local afterDiscount = subtotal - discount
    local tax = afterDiscount * data.getTax()
    return afterDiscount + tax
');

echo "Total: $" . number_format($total, 2);
// Total: $35.64
```

### Example 11: Registering a single function with registerFunction

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Register only one function inside a library (creates the lib if needed)
LuaEngine::registerFunction('triple', function($x) {
    return [$x * 3];
}, 'mathx');

$value = LuaEngine::execute('return mathx.triple(7)');
// Result: 21

// You can keep adding more functions to the same library
LuaEngine::registerFunction('inc', fn($x) => [$x + 1], 'mathx');
$next = LuaEngine::execute('return mathx.inc(21)');
// Result: 22
```

## API Reference

### Methods

#### `execute(string $script)`

Execute a Lua script and return the result.

**Parameters:**
- `$script` - Lua code to execute

**Returns:** The execution result (first element if array, otherwise the result as-is)

**Note:** To pass data to Lua scripts, use `registerLibrary()` to register PHP functions that provide access to your data.

**Example:**
```php
$result = LuaEngine::execute('return 42');
```

---

#### `evaluate(string $script): bool`

Evaluate a Lua expression and return a boolean result. Used for conditional checks.

**Parameters:**
- `$script` - Lua expression (will be wrapped with `return (...)`)

**Returns:** `true` or `false`

**Note:** To pass data to Lua expressions, use `registerLibrary()` to register PHP functions that provide access to your data.

**Example:**
```php
// Note: Functions must return arrays (LuaSandbox requirement)
LuaEngine::registerLibrary('data', [
    'getAge' => function() { return [20]; }, // Return as array
]);
$valid = LuaEngine::evaluate('data.getAge() >= 18');
// Result: true
```

---

#### `validate(string $script): bool`

Check if a Lua script has valid syntax without executing it.

**Parameters:**
- `$script` - Lua code to validate

**Returns:** `true` if valid, `false` otherwise

**Example:**
```php
if (LuaEngine::validate($userScript)) {
    $result = LuaEngine::execute($userScript);
}
```

---

#### `registerLibrary(array $functions, string $libname = 'php'): void`

Register a set of PHP functions as a Lua library.

This method wraps [LuaSandbox::registerLibrary()](https://www.php.net/manual/en/luasandbox.registerlibrary.php).

**Parameters:**
- `$functions` - An associative array where each key is a function name and each value is a corresponding PHP callable (function name string or callable)
- `$libname` - The name of the library. In the Lua state, the global variable of this name will be set to a table of functions

**Important Notes:**
- If a table with the same `$libname` already exists in Lua, the new functions will be **added** to the existing table (not replaced)
- Functions can be specified as:
  - **Callable closures**: `function($x) { return [$x * 2]; }`
  - **Function name strings**: `'myFunction'` (references a PHP function by name)
- In Lua, functions are accessed as `libname.functionName()`
- Functions that return values **must return arrays** - the first element is used as the Lua return value
- Functions that don't need to return values can return nothing (void)
- Functions can throw `LuaSandboxRuntimeError` or other LuaSandbox exceptions

**Example:**
```php
// Define a PHP function
function frobnosticate($v) {
    return [$v + 42]; // Return array for values
}

// Register library with both callables and function name strings
LuaEngine::registerLibrary([
    'frobnosticate' => 'frobnosticate', // Function name as string
    'output' => function($string) {      // Void function - no return needed
        echo "$string\n";
    },
    'add' => function($a, $b) {         // Returns value - must return array
        return [$a + $b];
    },
    'error' => function() {             // Can throw exceptions
        throw new \LuaSandboxRuntimeError("Something is wrong");
    },
    'multiply' => fn($a, $b) => [$a * $b], // Arrow function
], 'php');

// Use in Lua - functions are accessible as php.functionName()
$result = LuaEngine::execute('return php.frobnosticate(100)'); // 142
LuaEngine::execute('php.output("Hello from Lua")'); // Prints: Hello from Lua
$sum = LuaEngine::execute('return php.add(5, 3)'); // 8

// Add more functions to the same library (extends existing)
LuaEngine::registerLibrary('php', [
    'subtract' => function($a, $b) {
        return [$a - $b];
    },
]);
// Now php library has: frobnosticate, output, add, error, multiply, and subtract
```

---

#### `loadString(string $code): LuaSandboxFunction|false`

Load Lua code into a function without executing it.

This method wraps [LuaSandbox::loadString()](https://www.php.net/manual/en/luasandbox.loadstring.php).

**Parameters:**
- `$code` - Lua code string

**Returns:** `LuaSandboxFunction` on success, `false` on failure

**Example:**
```php
$func = LuaEngine::loadString('return 42');
if ($func !== false) {
    $result = $func->call();
    // Result: [42]
}
```

---

#### `callFunction(string $name, mixed ...$args): array|false`

Call a function in a Lua global variable.

This method wraps [LuaSandbox::callFunction()](https://www.php.net/manual/en/luasandbox.callfunction.php).

**Parameters:**
- `$name` - Lua function name (can contain "." for nested access like "string.match")
- `...$args` - Variable arguments to pass to the function

**Returns:** Array of return values or `false` on failure

**Example:**
```php
// Define function in Lua
LuaEngine::execute('_G["add"] = function(a, b) return a + b end');

// Call the function
$result = LuaEngine::callFunction('add', 5, 3);
// Result: [8]
```

---

#### `wrapPhpFunction(callable $function): LuaSandboxFunction|false`

Wrap a PHP callable for use in Lua.

This method wraps [LuaSandbox::wrapPhpFunction()](https://www.php.net/manual/en/luasandbox.wrapphpfunction.php).

**Parameters:**
- `$function` - PHP callable to wrap

**Returns:** `LuaSandboxFunction` or `false` on failure

**Example:**
```php
$phpFunc = LuaEngine::wrapPhpFunction(function($x) {
    return [$x * 2];
});

if ($phpFunc !== false) {
    // Call directly from PHP
    $ret = $phpFunc->call(21); // returns array [42]
}

// Tip: For exposing PHP functions to Lua, prefer registerFunction/registerLibrary
LuaEngine::registerFunction('double', function($x) { return [$x * 2]; }, 'custom');
$result = LuaEngine::execute('return custom.double(21)'); // 42
```

---

#### `registerFunction(string $functionName, callable $function, string $libName = 'php'): void`

Register a single PHP function inside a Lua library (creates the library if it doesn't exist yet).

**Parameters:**
- `$functionName` - Function name as exposed to Lua
- `$function` - PHP callable that returns an array when returning a value
- `$libName` - Library name (table in Lua)

**Example:**
```php
LuaEngine::registerFunction('triple', fn($x) => [$x * 3], 'mathx');
$res = LuaEngine::execute('return mathx.triple(7)'); // 21
```

---

#### `getRegisteredFunctions(string $libName = 'php'): array`

Get all registered functions for a specific library.

---

#### `getRegisteredLibrary(): array`

Get all libraries and their registered functions.

---

#### `isRegisteredFunction(string $libName = 'php', string $functionName): bool`

Check whether a function is registered within a given library.

---

#### `executeFile(string $filePath)`

Execute a Lua script from a file.

**Parameters:**
- `$filePath` - Path to Lua file

**Returns:** Execution result

**Note:** To pass data to Lua scripts, use `registerLibrary()` before calling this method to register PHP functions that provide access to your data.

**Throws:** `Exception` if file not found

**Example:**
```php
$result = LuaEngine::executeFile('/path/to/script.lua');
```

---

#### `setMemoryLimit(int $bytes): void`

Set the memory limit for the Lua environment.

This method wraps [LuaSandbox::setMemoryLimit()](https://www.php.net/manual/en/luasandbox.setmemorylimit.php).

**Parameters:**
- `$bytes` - Memory limit in bytes

**Example:**
```php
LuaEngine::setMemoryLimit(128 * 1024 * 1024); // 128MB
```

---

#### `setCPULimit(float $seconds): void`

Set the CPU time limit for the Lua environment.

This method wraps [LuaSandbox::setCPULimit()](https://www.php.net/manual/en/luasandbox.setcpulimit.php).

**Parameters:**
- `$seconds` - CPU limit in seconds (0 or false = no limit)

**Example:**
```php
LuaEngine::setCPULimit(60.0); // 60 seconds
```

---

#### `getSandbox(): ?LuaSandbox`

Get the underlying LuaSandbox instance for advanced usage.

**Returns:** `LuaSandbox` instance or `null` if not available

**Example:**
```php
$sandbox = LuaEngine::getSandbox();
if ($sandbox) {
    // Access advanced LuaSandbox methods directly
    // See: https://www.php.net/manual/en/class.luasandbox.php
    $memoryUsage = $sandbox->getMemoryUsage();
    $cpuUsage = $sandbox->getCPUUsage();
}
```

---

#### `getLastError(): ?string`

Get the last error message that occurred.

**Returns:** Last error message or `null`

**Example:**
```php
try {
    LuaEngine::execute('invalid syntax');
} catch (Exception $e) {
    $error = LuaEngine::getLastError();
}
```

---

#### `isAvailable(): bool`

Check if the LuaSandbox extension is available.

**Returns:** `true` if available, `false` otherwise

**Example:**
```php
if (LuaEngine::isAvailable()) {
    // Use Lua engine
}
```

## Security & Sandbox

This package uses the [PHP LuaSandbox extension](https://www.php.net/manual/en/class.luasandbox.php), which provides a secure sandboxed Lua environment by default.

### Disabled Functions & Modules

LuaSandbox automatically disables dangerous Lua functions and modules:

- ❌ `os` - Most operating system functions are disabled
  - ⚠️ **Exceptions:** `os.clock()`, `os.date()`, `os.difftime()`, `os.time()` are available
- ❌ `io` - All file I/O operations are disabled
- ❌ `require`, `package`, `module` - Module loading and package management disabled
- ❌ `dofile`, `loadfile` - File operations disabled
- ❌ `load`, `loadstring` - Dynamic code loading disabled
- ❌ `debug` - Most debugging functions disabled
  - ⚠️ **Exception:** `debug.traceback()` is available
- ❌ `print` - Standard output disabled

### Available Modules

These standard Lua modules are available and safe to use:

- ✅ `math` - Mathematical functions (`math.sin`, `math.cos`, `math.floor`, etc.)
- ✅ `string` - String manipulation (`string.upper`, `string.lower`, `string.match`, etc.)
- ✅ `table` - Table operations (`table.insert`, `table.remove`, `table.concat`, etc.)

### Resource Limits

LuaSandbox provides built-in resource limits to prevent resource exhaustion:

- **CPU Time Limits**: Controlled via `setCPULimit()` or configuration option `cpu_limit`
  - See: [LuaSandbox::setCPULimit()](https://www.php.net/manual/en/luasandbox.setcpulimit.php)
- **Memory Limits**: Controlled via `setMemoryLimit()` or configuration option `memory_limit`
  - See: [LuaSandbox::setMemoryLimit()](https://www.php.net/manual/en/luasandbox.setmemorylimit.php)
- **Automatic Error Handling**: LuaSandbox throws specific exceptions:
  - `LuaSandboxTimeoutError` - When CPU limit is exceeded
  - `LuaSandboxMemoryError` - When memory limit is exceeded
  - `LuaSandboxSyntaxError` - For syntax errors
  - `LuaSandboxRuntimeError` - For runtime errors

### Additional Security Features

You can access additional LuaSandbox features through `getSandbox()`:

- Monitor resource usage: `getMemoryUsage()`, `getCPUUsage()`, `getPeakMemoryUsage()`
- Enable profiling: `enableProfiler()`, `disableProfiler()`, `getProfilerFunctionReport()`
- Load precompiled binaries: `loadBinary()`
- Pause/unpause CPU timer: `pauseUsageTimer()`, `unpauseUsageTimer()`

For complete documentation, see the [official PHP LuaSandbox documentation](https://www.php.net/manual/en/class.luasandbox.php).

## Error Handling

This package provides comprehensive error handling that wraps LuaSandbox exceptions with user-friendly messages.

### Exception Types

The following exceptions may be thrown:

- `LuaSandboxSyntaxError` - Caught and wrapped in `Exception` with message "Lua syntax error: ..."
- `LuaSandboxRuntimeError` - Caught and wrapped in `Exception` with message "Lua runtime error: ..."
- `LuaSandboxTimeoutError` - Caught and wrapped in `Exception` with message "Lua execution timeout: ..."
- `LuaSandboxMemoryError` - Caught and wrapped in `Exception` with message "Lua memory limit exceeded: ..."
- `LuaSandboxError` - Generic error caught and wrapped in `Exception` with message "Lua script error: ..."

### Error Logging

Errors are automatically logged according to your configuration:

```php
try {
    $result = LuaEngine::execute('invalid syntax');
} catch (\Exception $e) {
    // Error message
    echo 'Error: ' . $e->getMessage();
    
    // Get last error (same as exception message in this case)
    echo 'Last error: ' . LuaEngine::getLastError();
    
    // Error is also logged to Laravel logs based on log_level config
}
```

### Accessing LuaSandbox Exceptions Directly

If you need access to the original LuaSandbox exceptions, you can catch them directly:

```php
use LuaSandbox\LuaSandboxSyntaxError;
use LuaSandbox\LuaSandboxRuntimeError;
use LuaSandbox\LuaSandboxTimeoutError;
use LuaSandbox\LuaSandboxMemoryError;

try {
    $sandbox = LuaEngine::getSandbox();
    $function = $sandbox->loadString('invalid syntax');
} catch (LuaSandboxSyntaxError $e) {
    // Handle syntax error
    echo 'Syntax error: ' . $e->getMessage();
}
```

## Testing

Run the test suite:

```bash
php vendor/bin/phpunit
```

Or using composer scripts (if configured):

```bash
composer test
```

### Test Requirements

Tests require the LuaSandbox extension to be installed. If the extension is not available, tests will be skipped automatically.

## References

- [PHP LuaSandbox Extension Documentation](https://www.php.net/manual/en/class.luasandbox.php)
- [LuaSandbox Methods Reference](https://www.php.net/manual/en/class.luasandbox.php#class.luasandbox-methods)
- [Lua Language Documentation](https://www.lua.org/manual/)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

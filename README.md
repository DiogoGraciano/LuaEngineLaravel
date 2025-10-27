# Laravel Lua Engine

A secure Lua script execution engine for Laravel applications using the PHP-Lua extension.

## Features

- 🔒 **Safe Sandbox**: Automatic removal of dangerous Lua functions (os, io, require, etc.)
- ⚡ **Easy Integration**: Built specifically for Laravel
- 🎯 **Flexible Configuration**: Control which Lua modules are allowed
- 📊 **Data Binding**: Pass PHP variables to Lua scripts
- 🧪 **Resource Limits**: Timeout and memory limit controls
- 📝 **Error Logging**: Configurable logging levels
- ✅ **Validation**: Check script syntax before execution

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Lua PHP extension (`pecl install lua`)

## Installation

1. Install the Lua PHP extension:
```bash
sudo pecl install lua
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
    'sandbox' => [
        // Allow access to math functions
        'allow_math' => true,
        
        // Allow access to string functions
        'allow_string' => true,
        
        // Allow access to table functions
        'allow_table' => true,

        // Custom functions that can be called from Lua
        'global_functions' => [
            // 'my_function' => [MyClass::class, 'myMethod'],
        ],
    ],

    'options' => [
        // Timeout in seconds (0 = no timeout)
        'timeout' => 30,
        
        // Maximum memory in bytes (0 = no limit)
        'memory_limit' => 67108864, // 64MB
        
        // Log errors to Laravel log
        'log_errors' => true,
        
        // Log level for Lua errors
        'log_level' => 'error', // 'error', 'warning', 'info', 'debug'
    ],
];
```

## Basic Usage

### Using the Facade

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Execute a simple Lua script
$result = LuaEngine::execute('return 42');
echo $result; // 42

// Pass data to Lua
$data = ['name' => 'John', 'age' => 30];
$result = LuaEngine::execute('return data.name', $data);
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

// Access nested data
$name = LuaEngine::execute('return data.user.name', $data);
// Result: "Alice"

// Calculate average score (note: Lua arrays start at 1)
$avg = LuaEngine::execute(
    'return (data.user.scores[1] + data.user.scores[2] + data.user.scores[3]) / 3',
    $data
);
// Result: 84.33333333333333
```

### Example 3: Conditional Evaluation

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Use evaluate() for boolean conditions
$data = ['age' => 25, 'minimumAge' => 18];

$canVote = LuaEngine::evaluate('data.age >= data.minimumAge', $data);
// Result: true

$isAdult = LuaEngine::evaluate('data.age >= 21', $data);
// Result: true
```

### Example 4: String Operations

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

$data = ['message' => 'hello world'];

// Transform string
$upper = LuaEngine::execute('return string.upper(data.message)', $data);
// Result: "HELLO WORLD"

// Get string length
$length = LuaEngine::execute('return #data.message', $data);
// Result: 11
```

### Example 5: Registering Custom PHP Functions

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Register a custom function
LuaEngine::registerFunction('double', function($x) {
    return $x * 2;
});

// Use in Lua
$result = LuaEngine::execute('return double(21)');
// Result: 42

// Register multiple functions at once
LuaEngine::registerFunctions([
    'square' => function($x) { return $x * $x; },
    'cube' => function($x) { return $x * $x * $x; },
]);

$square = LuaEngine::execute('return square(5)'); // 25
$cube = LuaEngine::execute('return cube(3)'); // 27
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
$data = ['value' => 100];
$result = LuaEngine::executeFile('/path/to/script.lua', $data);
```

### Example 8: Using assign() to Set Variables

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Manually assign variables
LuaEngine::assign('counter', 0);
LuaEngine::assign('user', ['name' => 'Bob', 'active' => true]);

// Access in script
$result = LuaEngine::execute('return user.name');
// Result: "Bob"
```

### Example 9: Calling Lua Functions

```php
use DiogoGraciano\LuaEngine\Facades\LuaEngine;

// Define a function in Lua
LuaEngine::execute('
    function multiply(a, b)
        return a * b
    end
');

// Call the function
$result = LuaEngine::call('multiply', [6, 7]);
// Result: 42
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

$total = LuaEngine::execute(
    '
    local subtotal = 0
    for i = 1, #data.items do
        subtotal = subtotal + data.items[i].price * data.items[i].quantity
    end
    local discount = subtotal * data.discount
    local afterDiscount = subtotal - discount
    local tax = afterDiscount * data.tax
    return afterDiscount + tax
    ',
    $order
);

echo "Total: $" . number_format($total, 2);
// Total: $35.64
```

## API Reference

### Methods

#### `execute(string $script, array $data = [])`

Execute a Lua script and return the result.

**Parameters:**
- `$script` - Lua code to execute
- `$data` - Array of data available as `data` variable in Lua

**Returns:** The execution result

**Example:**
```php
$result = LuaEngine::execute('return 42');
```

---

#### `evaluate(string $script, array $data = []): bool`

Evaluate a Lua expression and return a boolean result. Used for conditional checks.

**Parameters:**
- `$script` - Lua expression (will be wrapped with `return (...)`)
- `$data` - Array of data available as `data` variable in Lua

**Returns:** `true` or `false`

**Example:**
```php
$valid = LuaEngine::evaluate('data.age >= 18', ['age' => 20]);
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

#### `registerFunction(string $name, callable $callback): void`

Register a PHP function that can be called from Lua.

**Parameters:**
- `$name` - Function name in Lua
- `$callback` - PHP callable

**Example:**
```php
LuaEngine::registerFunction('add', function($a, $b) {
    return $a + $b;
});
```

---

#### `registerFunctions(array $functions): void`

Register multiple PHP functions at once.

**Parameters:**
- `$functions` - Associative array of `[name => callback]`

**Example:**
```php
LuaEngine::registerFunctions([
    'double' => fn($x) => $x * 2,
    'square' => fn($x) => $x * $x,
]);
```

---

#### `assign(string $name, mixed $value)`

Assign a PHP variable to the Lua environment.

**Parameters:**
- `$name` - Variable name in Lua
- `$value` - Variable value (array, string, number, etc.)

**Returns:** `$this` on success, `null` on failure

**Example:**
```php
LuaEngine::assign('counter', 0);
LuaEngine::assign('user', ['name' => 'John']);
```

---

#### `call(string $function, array $arguments = [], int $useSelf = 0)`

Call a Lua function with arguments.

**Parameters:**
- `$function` - Lua function name to call
- `$arguments` - Arguments to pass to the function
- `$useSelf` - Whether to use self context

**Returns:** Result of the called function or `null` on failure

**Example:**
```php
$result = LuaEngine::call('add', [5, 3]);
```

---

#### `executeFile(string $filePath, array $data = [])`

Execute a Lua script from a file.

**Parameters:**
- `$filePath` - Path to Lua file
- `$data` - Array of data available as `data` variable in Lua

**Returns:** Execution result

**Throws:** `Exception` if file not found

**Example:**
```php
$result = LuaEngine::executeFile('/path/to/script.lua', $data);
```

---

#### `clearGlobals(): void`

Clear all global variables in the Lua environment.

**Example:**
```php
LuaEngine::clearGlobals();
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

Check if the Lua extension is available.

**Returns:** `true` if available, `false` otherwise

**Example:**
```php
if (LuaEngine::isAvailable()) {
    // Use Lua engine
}
```

## Security & Sandbox

The Lua Engine automatically sandboxes scripts by removing dangerous functions:

- ❌ `os` - Operating system access
- ❌ `io` - File I/O operations
- ❌ `require` - Module loading
- ❌ `dofile`, `loadfile` - File operations
- ❌ `load`, `loadstring` - Dynamic code loading
- ❌ `debug` - Debugging functions
- ❌ `package`, `module` - Package management
- ❌ `getfenv`, `setfenv` - Environment manipulation

**Allowed modules (configurable):**
- ✅ `math` - Mathematical functions
- ✅ `string` - String manipulation
- ✅ `table` - Table operations

You can disable these modules in the configuration:

```php
'sandbox' => [
    'allow_math' => false,
    'allow_string' => false,
    'allow_table' => false,
],
```

## Error Handling

Errors are automatically logged according to your configuration:

```php
try {
    $result = LuaEngine::execute('invalid syntax');
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage();
    echo 'Last error: ' . LuaEngine::getLastError();
}
```

## Testing

Run the test suite:

```bash
composer test
```

Or with coverage:

```bash
composer test-coverage
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

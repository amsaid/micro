# SdFramework Micro

A lightweight and extensible PHP micro framework with modern features.

## Features

- Dependency Injection Container with autowiring
- Event System with PSR-14 compatibility
- Lightweight ORM with Active Record pattern
- PSR compliant interfaces
- Modern PHP 8.1+ features
- Routing with middleware support
- Request/Response handling
- Configuration management (PHP, JSON, YAML)
- Error handling
- Query Builder with fluent interface
- View/Template system with layouts
- Validation system
- Cache system

## Installation

```bash
composer require sdframework/micro
```

## Quick Start

### Basic Application Setup

```php
use SdFramework\Application;

$app = new Application(__DIR__ . '/config/app.php');
$router = $app->getRouter();

// Define routes
$router->get('/', function() {
    return 'Hello World!';
});

$router->get('/users/{id}', [UserController::class, 'show']);

// Add middleware
$router->addMiddleware(AuthMiddleware::class);

// Run the application
$app->run();
```

### Routing & Middleware

```php
use SdFramework\Middleware\MiddlewareInterface;
use SdFramework\Http\Request;
use SdFramework\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        if (!$this->isAuthenticated($request)) {
            return Response::json_response(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}

// Add middleware to specific route
$router->get('/admin', [AdminController::class, 'index'], [AuthMiddleware::class]);
```

### Dependency Injection

```php
use SdFramework\Container\Container;

$container = new Container();

// Auto-wiring
class UserService {
    public function __construct(private DatabaseService $db) {}
}

$userService = $container->get(UserService::class);

// Manual binding
$container->set(DatabaseInterface::class, DatabaseService::class);
```

### Event System

```php
use SdFramework\Event\EventDispatcher;
use SdFramework\Event\Event;

class UserCreatedEvent extends Event {
    public function __construct(public readonly User $user) {}
}

$dispatcher = new EventDispatcher();

$dispatcher->addListener(UserCreatedEvent::class, function(UserCreatedEvent $event) {
    // Handle event
});

$dispatcher->dispatch(new UserCreatedEvent($user));
```

### ORM Usage

```php
use SdFramework\Database\Model;

class User extends Model {
    protected static string $table = 'users';
}

// Create
$user = new User([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
$user->save();

// Read
$user = User::find(1);
$allUsers = User::all();

// Update
$user->name = 'Jane Doe';
$user->save();

// Delete
$user->delete();
```

### Query Builder

```php
use SdFramework\Database\QueryBuilder;

$users = $queryBuilder
    ->table('users')
    ->select(['id', 'name', 'email'])
    ->where('active', true)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Complex queries
$stats = $queryBuilder
    ->table('orders')
    ->select(['user_id', 'COUNT(*) as order_count', 'SUM(total) as total_spent'])
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->where('orders.status', 'completed')
    ->groupBy('user_id')
    ->having('order_count', '>', 5)
    ->get();
```

### View System

```php
// views/layouts/main.php
<!DOCTYPE html>
<html>
<head>
    <title><?= $this->yield('title') ?></title>
</head>
<body>
    <?= $this->yield('content') ?>
</body>
</html>

// views/users/index.php
<?php $this->extend('layouts.main') ?>

<?php $this->section('title') ?>
    User List
<?php $this->endSection() ?>

<?php $this->section('content') ?>
    <h1>Users</h1>
    <?php foreach ($users as $user): ?>
        <div><?= $this->escape($user->name) ?></div>
    <?php endforeach ?>
<?php $this->endSection() ?>

// Usage in controller
$view = new View();
$view->setViewPath(__DIR__ . '/views');
echo $view->render('users.index', ['users' => $users]);
```

### Validation

```php
use SdFramework\Validation\Validator;

$validator = new Validator($_POST, [
    'name' => 'required|min:3',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed',
    'age' => 'numeric|min:18'
]);

if (!$validator->validate()) {
    $errors = $validator->errors();
    // Handle validation errors
}
```

### Cache System

```php
use SdFramework\Cache\FileCache;

$cache = new FileCache(__DIR__ . '/cache');

// Store data
$cache->set('user.1', $user, 3600); // Cache for 1 hour

// Retrieve data
$user = $cache->get('user.1', function() {
    // Default value if cache miss
    return User::find(1);
});

// Check existence
if ($cache->has('user.1')) {
    // Cache hit
}

// Remove data
$cache->delete('user.1');

// Clear all cache
$cache->clear();
```

### Configuration

```php
// config/app.php
return [
    'app' => [
        'name' => 'My App',
        'debug' => true,
    ],
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'myapp',
        'username' => 'root',
        'password' => ''
    ]
];

// Usage
$config = $app->getConfig();
$debug = $config->get('app.debug', false);
```

### Request & Response

```php
use SdFramework\Http\Request;
use SdFramework\Http\Response;

$router->post('/api/users', function(Request $request) {
    $data = $request->getJson();
    $user = new User($data);
    $user->save();
    
    return Response::json_response([
        'message' => 'User created',
        'user' => $user
    ], 201);
});
```

## Requirements

- PHP 8.1 or higher
- PDO extension for database operations

## License

MIT License

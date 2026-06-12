<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$vendor = dirname(__DIR__, 2) . '/thinkphp_web/vendor/autoload.php';

if (!file_exists($vendor)) {
    fwrite(STDERR, "ThinkPHP integration vendor autoload not found.\n");
    exit(1);
}

require $vendor;
require $root . '/src/Support/helpers.php';

spl_autoload_register(function (string $class) use ($root): void {
    $prefix = 'Thinkrix\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$classes = [
    Thinkrix\Controllers\UserController::class,
    Thinkrix\Controllers\RoleController::class,
    Thinkrix\Controllers\PermissionController::class,
    Thinkrix\Controllers\MenuController::class,
    Thinkrix\Controllers\DictController::class,
    Thinkrix\Models\AdminUser::class,
    Thinkrix\Models\Menu::class,
    Thinkrix\Models\Role::class,
    Thinkrix\Services\AuthService::class,
    Thinkrix\Services\PermissionService::class,
];

foreach ($classes as $class) {
    if (!class_exists($class)) {
        fwrite(STDERR, "Unable to load {$class}.\n");
        exit(1);
    }
}

$treeFixtures = [[
    'id' => 1,
    'allChildren' => [[
        'id' => 2,
        'allChildren' => [],
    ]],
]];

foreach ([
    [Thinkrix\Controllers\MenuController::class, 'transformMenuChildren'],
    [Thinkrix\Controllers\PermissionController::class, 'transformPermissionChildren'],
] as [$controllerClass, $methodName]) {
    $reflection = new ReflectionClass($controllerClass);
    $controller = $reflection->newInstanceWithoutConstructor();
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);
    $tree = $method->invoke($controller, $treeFixtures);

    if (($tree[0]['children'][0]['id'] ?? null) !== 2
        || array_key_exists('allChildren', $tree[0])
        || array_key_exists('allChildren', $tree[0]['children'][0])) {
        fwrite(STDERR, "{$controllerClass} must convert ThinkORM allChildren relations to recursive children trees.\n");
        exit(1);
    }
}

$requestClass = app\Request::class;
if (!class_exists($requestClass)) {
    require dirname(__DIR__, 2) . '/thinkphp_web/app/Request.php';
}

$request = (new $requestClass())->withHeader(['Accept' => 'text/html']);
$middleware = new Thinkrix\Middleware\HandleApiException();
$response = $middleware->handle($request, static function ($request) {
    return think\Response::create(['code' => 0, 'data' => ['ok' => true]], $request->isJson() ? 'json' : 'html');
});

if (!$response instanceof think\response\Json) {
    fwrite(STDERR, "Thinkrix API middleware must force array responses to JSON without relying on the client Accept header.\n");
    exit(1);
}
json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

echo "runtime regression checks passed\n";

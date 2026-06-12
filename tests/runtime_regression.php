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

if (!function_exists('config')) {
    function config(string $name, mixed $default = null): mixed
    {
        return $default;
    }
}

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

$dataTable = Thinkrix\Schema\Components\Business\DataTable::make()->rowKey('name')->toArray();
if (($dataTable['props']['rowKey'] ?? null) !== '{{ row => row.name }}') {
    fwrite(STDERR, "DataTable::rowKey must generate a row resolver function for JsonDataTable.\n");
    exit(1);
}

$moduleControllerReflection = new ReflectionClass(Thinkrix\Controllers\ModuleController::class);
$moduleController = $moduleControllerReflection->newInstanceWithoutConstructor();
$installedUi = $moduleControllerReflection->getMethod('installedUi');
$installedUi->setAccessible(true);
$moduleSchema = $installedUi->invoke($moduleController)['data'];
$moduleContent = $moduleSchema['children'][0] ?? [];
$moduleTable = $moduleContent['children'][1] ?? [];

if (($moduleSchema['props']['style']['height'] ?? null) !== '100%'
    || ($moduleSchema['props']['contentStyle']['display'] ?? null) !== 'flex'
    || ($moduleContent['props']['vertical'] ?? null) !== true
    || ($moduleContent['props']['style']['height'] ?? null) !== '100%'
    || ($moduleTable['props']['flexHeight'] ?? null) !== true
    || ($moduleTable['props']['style']['flex'] ?? null) !== '1 1 0%') {
    fwrite(STDERR, "Module management table must use the same full-height flex layout as CrudPage.\n");
    exit(1);
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

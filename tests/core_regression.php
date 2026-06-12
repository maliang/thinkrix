<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

function check(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

function source(string $path): string
{
    global $root;
    return file_get_contents($root . DIRECTORY_SEPARATOR . $path);
}

$menu = source('src/Models/Menu.php');
check(!str_contains($menu, 'relationLoaded('), 'Menu must not call Laravel relationLoaded().');
check(!str_contains($menu, 'isNotEmpty('), 'Menu must not call Laravel Collection::isNotEmpty().');

$userController = source('src/Controllers/UserController.php');
check(
    str_contains($userController, 'protected function updateStatus(int $id): array'),
    'UserController::updateStatus must match CrudController signature.'
);

$permissionService = source('src/Services/PermissionService.php');
check(
    !str_contains($permissionService, 'Db::name('),
    'PermissionService must not call the non-static Db::name() statically.'
);

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . DIRECTORY_SEPARATOR . 'src')) as $sourceFile) {
    if ($sourceFile->isFile() && $sourceFile->getExtension() === 'php') {
        check(!str_contains(file_get_contents($sourceFile->getPathname()), 'Db::name('), $sourceFile->getFilename() . ' must not call the non-static Db::name() statically.');
    }
}

$adminUser = source('src/Models/AdminUser.php');
check(!str_contains($adminUser, 'collect([])'), 'AdminUser must not call Laravel collect().');
check(
    str_contains($adminUser, "belongsToMany(\$roleModel, 'model_has_roles', 'role_id', 'model_id')"),
    'AdminUser roles relation must use ThinkORM key order.'
);

$role = source('src/Models/Role.php');
check(
    str_contains($role, "belongsToMany(Permission::class, 'role_has_permissions', 'permission_id', 'role_id')"),
    'Role permissions relation must use ThinkORM key order.'
);

foreach (['RoleController.php', 'PermissionController.php', 'MenuController.php'] as $controller) {
    $contents = source('src/Controllers/' . $controller);
    check(str_contains($contents, 'getStoreRules'), "{$controller} must define store validation.");
    check(str_contains($contents, 'getUpdateRules'), "{$controller} must define update validation.");
}

$dict = source('src/Controllers/DictController.php');
check(!preg_match('/success\((?:DictGroup|DictItem)::create\(/', $dict), 'Dict create responses must not pass models as success() message.');
check(!str_contains($dict, 'success(null,'), 'Dict responses must not pass null as success() message.');

$baseController = source('src/Controllers/Controller.php');
check(
    str_contains($baseController, 'array_intersect_key($data, $rules)'),
    'Controller validation must return only validated fields.'
);

$export = source('src/Exports/BaseExport.php');
check(!str_contains($export, 'getCellByColumnAndRow'), 'Export must use supported PhpSpreadsheet cell APIs.');
check(!str_contains($export, 'getStyleByColumnAndRow'), 'Export must use supported PhpSpreadsheet style APIs.');

$dataTable = source('src/Schema/Components/Business/DataTable.php');
check(
    str_contains($dataTable, "parent::__construct('JsonDataTable')"),
    'DataTable must use the JsonDataTable adapter so scoped column slots are rendered.'
);
check(
    str_contains($dataTable, 'protected array $tableSlots = []')
        && str_contains($dataTable, "\$this->slot(\$column, \$config['content'], \$config['slotProps'])"),
    'DataTable columns must register scoped column slots.'
);

$moduleController = source('src/Controllers/ModuleController.php');
check(
    !str_contains($moduleController, "->page('{{")
        && !str_contains($moduleController, "->pageSize('{{")
        && !str_contains($moduleController, "->itemCount('{{"),
    'ModuleController pagination helpers must receive raw expressions to avoid nested template braces.'
);

$themeConfig = source('config/thinkrix.php');
check(
    preg_match("/'footer'\s*=>\s*\[\s*'visible'\s*=>\s*false,/s", $themeConfig) === 1,
    'Thinkrix theme config must hide the global footer by default.'
);

require_once $root . '/src/Exceptions/ApiException.php';
$exception = new Thinkrix\Exceptions\ApiException('invalid', 40022);
check($exception->getErrorCode() === 40022, 'ApiException second integer argument must be treated as error code.');
check($exception->getData() === null, 'ApiException error code must not be stored as response data.');

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "core regression checks passed\n";

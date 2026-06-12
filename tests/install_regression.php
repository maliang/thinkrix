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

$composer = json_decode(source('composer.json'), true, flags: JSON_THROW_ON_ERROR);
check(
    isset($composer['require']['topthink/think-migration']),
    'composer.json must require topthink/think-migration.'
);
check(
    str_contains($composer['require']['topthink/think-orm'] ?? '', '^4.0'),
    'composer.json must remain compatible with ThinkORM 4.'
);

$install = source('src/Commands/InstallCommand.php');
foreach (['dict_groups', 'dict_items', 'notification_categories', 'notification_messages'] as $table) {
    check(
        str_contains($install, "CREATE TABLE IF NOT EXISTS `{$table}`"),
        "InstallCommand fallback must create {$table}."
    );
}
check(
    !str_contains($install, '请手动运行 php think migrate:run'),
    'InstallCommand must not claim migrations ran while only asking the user to run them.'
);
check(!str_contains($install, "'--path'"), 'InstallCommand must not pass unsupported --path to migrate:run.');
check(
    str_contains($install, 'MigrationRun')
        && str_contains($install, 'protected function getPath(): string'),
    'InstallCommand must run package migrations through a path-aware command.'
);
check(!str_contains($install, 'Db::execute('), 'InstallCommand must not call the unavailable static Db::execute().');
check(!str_contains($install, 'Db::connect('), 'InstallCommand must not call the non-static Db::connect() statically.');
check(!str_contains($install, 'Db::name('), 'InstallCommand must not call the non-static Db::name() statically.');
check(str_contains($install, '$this->app->db->execute('), 'InstallCommand fallback SQL must execute through the application database instance.');
check(str_contains($install, '$this->app->db->name('), 'InstallCommand queries must use the application database instance.');
check(str_contains($install, 'Setting::setValue('), 'InstallCommand must use the ORM-compatible setting write API.');
check(!str_contains($install, "\$password = 'password'"), 'InstallCommand must not create a fixed default password.');
check(str_contains($install, "addOption('password'"), 'InstallCommand must accept an explicit administrator password.');
check(str_contains($install, 'askHidden('), 'Interactive installation must request a hidden administrator password.');
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . DIRECTORY_SEPARATOR . 'src/Commands')) as $commandFile) {
    if ($commandFile->isFile() && $commandFile->getExtension() === 'php') {
        check(!str_contains(file_get_contents($commandFile->getPathname()), '->warn('), $commandFile->getFilename() . ' must not call unsupported Output::warn().');
    }
}

$moduleService = source('src/Services/ModuleService.php');
check(
    substr_count($moduleService, "'enabled' => true") === 1
        && str_contains($moduleService, "new Module(['name' => \$name, 'enabled' => true])"),
    'ModuleService sync may default new modules to enabled but must preserve existing values.'
);
check(
    !str_contains($moduleService, 'updateOrCreate('),
    'ModuleService must not call unsupported ThinkORM updateOrCreate().'
);
check(
    str_contains($moduleService, "if (!file_exists(\$moduleJsonPath))"),
    'ModuleService must ignore ordinary app directories without module.json.'
);

$makeBackend = source('src/Commands/MakeBackendCommand.php');
check(!str_contains($makeBackend, "'required'"), 'MakeBackendCommand must use ThinkPHP argument constants.');
check(!str_contains($makeBackend, "'optional'"), 'MakeBackendCommand must use ThinkPHP option constants.');
foreach ([
    "'route/app.php' => 'routes.stub'",
    "'controller/Index.php' => 'auth_controller.stub'",
    "'controller/Menu.php' => 'menu_controller.stub'",
    "'controller/Permission.php' => 'permission_controller.stub'",
    "'controller/Role.php' => 'role_controller.stub'",
    "'controller/User.php' => 'user_controller.stub'",
    "'controller/System.php' => 'system_controller.stub'",
    "'middleware/BackendContext.php' => 'backend_context_middleware.stub'",
] as $mapping) {
    check(str_contains($makeBackend, $mapping), "MakeBackendCommand must generate {$mapping}.");
}
check(
    substr_count($makeBackend, "'{{LOWER_NAME}}' => \$lowerName") >= 2,
    'MakeBackendCommand must replace the lower-case module name in config and generated files.'
);

foreach ([
    'config.stub',
    'module.json.stub',
    'routes.stub',
    'auth_controller.stub',
    'menu_controller.stub',
    'permission_controller.stub',
    'role_controller.stub',
    'user_controller.stub',
    'system_controller.stub',
    'backend_context_middleware.stub',
    'user_model.stub',
    'user_migration.stub',
] as $stub) {
    $path = 'stubs/backend/' . $stub;
    check(is_file($root . DIRECTORY_SEPARATOR . $path), "Missing backend stub: {$stub}.");
}

$routesStub = source('stubs/backend/routes.stub');
foreach (['@store', '@show', '@update', '@destroy', 'Authenticate::class'] as $requiredRoutePart) {
    check(str_contains($routesStub, $requiredRoutePart), "routes.stub must contain {$requiredRoutePart}.");
}
check(str_contains($routesStub, 'BackendContext::class'), 'routes.stub must apply the generated backend config context.');
check(str_contains($routesStub, 'HandleApiException::class'), 'routes.stub must force generated backend API responses to JSON.');
check(!str_contains($routesStub, 'Route::resource'), 'routes.stub must not use incompatible ThinkPHP resource action names.');
check(str_contains($routesStub, 'CheckPermission::class'), 'routes.stub must protect generated backend management routes.');

$moduleRouteStub = source('stubs/modules/route.stub');
check(str_contains($moduleRouteStub, 'HandleApiException::class'), 'Module route stubs must force array API responses to JSON.');

$stubReplacements = [
    '{{namespace}}' => 'app\\Merchant\\controller',
    '{{NAME}}' => 'Merchant',
    '{{TITLE}}' => 'Merchant Admin',
    '{{PATH}}' => '/merchant',
    '{{API_PREFIX}}' => 'api/merchant',
    '{{LOWER_NAME}}' => 'merchant',
    '{{ADMIN_USERNAME}}' => 'admin',
    '{{ADMIN_PASSWORD_HASH}}' => '$2y$10$test',
    '{{MIGRATION_NAME}}' => 'Merchant',
];
foreach (glob($root . DIRECTORY_SEPARATOR . 'stubs/backend/*.stub') as $stubPath) {
    $generated = str_replace(array_keys($stubReplacements), array_values($stubReplacements), file_get_contents($stubPath));
    check(!str_contains($generated, '{{'), basename($stubPath) . ' must not leave unresolved placeholders.');
    check(!str_contains($generated, 'Db::name('), basename($stubPath) . ' must not call the non-static Db::name() statically.');

    if (str_starts_with($generated, '<?php')) {
        try {
            token_get_all($generated, TOKEN_PARSE);
        } catch (ParseError $e) {
            check(false, basename($stubPath) . ' generates invalid PHP: ' . $e->getMessage());
        }
    } elseif (str_ends_with($stubPath, 'module.json.stub')) {
        try {
            json_decode($generated, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            check(false, 'module.json.stub generates invalid JSON: ' . $e->getMessage());
        }
    }
}

$readme = source('README.md');
check(
    !str_contains($readme, '完整实现了 Lartrix 的全部功能'),
    'README must not claim full Lartrix feature parity.'
);

$service = source('src/ThinkrixService.php');
check(
    !str_contains($service, 'protected function loadRoutesFrom'),
    'ThinkrixService must use the framework RouteLoaded lifecycle instead of overriding loadRoutesFrom().'
);
check(
    str_contains($service, 'loadEnabledModules()'),
    'ThinkrixService must load enabled module resources.'
);
check(
    str_contains($service, 'function (\\think\\App $app)'),
    'ThinkrixService container factories must type-hint the App dependency.'
);

$dictMigration = source('database/migrations/20240101000001_create_dict_tables.php');
check(
    str_contains($dictMigration, "'group_id', 'integer', ['signed' => false"),
    'Dict item group_id must be unsigned to match the unsigned dict_groups primary key.'
);
foreach (['hasTable(', 'hasColumn(', 'hasForeignKey('] as $recoveryCheck) {
    check(str_contains($dictMigration, $recoveryCheck), "Dict migration must recover partial installations using {$recoveryCheck}.");
}
foreach ([
    'database/migrations/20240101000001_create_dict_tables.php',
    'database/migrations/20260227000001_create_notification_categories_table.php',
    'database/migrations/20260227000002_create_notification_messages_table.php',
] as $migrationPath) {
    check(
        str_contains(source($migrationPath), "->addTimestamps('created_at', 'updated_at')"),
        "{$migrationPath} must use the timestamp names expected by Thinkrix models."
    );
}
foreach ([
    'database/migrations/20260227000001_create_notification_categories_table.php',
    'database/migrations/20260227000002_create_notification_messages_table.php',
] as $migrationPath) {
    check(str_contains(source($migrationPath), 'hasTable('), "{$migrationPath} must tolerate a table created by fallback installation.");
}
check(
    str_contains(source('database/migrations/20260227000003_add_from_guard_to_notification_messages.php'), "hasColumn('from_guard')"),
    'from_guard migration must tolerate a column created by fallback installation.'
);

$settingModel = source('src/Models/Setting.php');
check(!str_contains($settingModel, 'public static function set('), 'Setting must not override the non-static ORM Model::set() method.');
check(str_contains($settingModel, 'public static function setValue('), 'Setting must expose a non-conflicting setting write API.');

$removeBackend = source('src/Commands/RemoveBackendCommand.php');
check(!str_contains($removeBackend, "'required'"), 'RemoveBackendCommand must use ThinkPHP argument constants.');
check(str_contains($removeBackend, 'syncModules()'), 'RemoveBackendCommand must synchronize module registration after deletion.');
check(str_contains($removeBackend, "addOption('force'"), 'RemoveBackendCommand must require confirmation unless forced.');
check(str_contains($removeBackend, "addOption('keep-data'"), 'RemoveBackendCommand must support preserving backend data explicitly.');
check(str_contains($removeBackend, 'ModuleMigrationRollback'), 'RemoveBackendCommand must clean generated backend migrations before deleting files.');
check(
    str_contains($makeBackend, "DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php'"),
    'MakeBackendCommand must place config where ModuleLoader can load it.'
);
check(str_contains($makeBackend, 'syncModules()'), 'MakeBackendCommand must register the generated backend module.');
check(str_contains($makeBackend, "addOption('admin-password'"), 'MakeBackendCommand must support secure initial administrator credentials.');
check(str_contains($makeBackend, 'thinkrix:module-migrate'), 'MakeBackendCommand must explain how to initialize the generated backend database.');

$moduleLoader = source('src/Support/ModuleLoader.php');
check(
    str_contains($moduleLoader, 'public function loadEnabledModuleCommands(): void'),
    'ModuleLoader must expose enabled module command discovery.'
);
check(
    str_contains($service, '$loader->getRegisteredCommands()'),
    'ThinkrixService must register discovered module commands with the console.'
);

$moduleGenerator = source('src/Support/ModuleGenerator.php');
check(str_contains($moduleGenerator, "'{{TITLE}}'"), 'ModuleGenerator must apply the module title option.');

$migrateCommand = source('src/Commands/Module/MigrateCommand.php');
check(!str_contains($migrateCommand, '$migration = require $file'), 'Module migrations must use think-migration instead of requiring an object.');
check(str_contains($migrateCommand, 'ModuleMigrationRun'), 'Module migrations must use a path-aware think-migration runner.');

$seedCommand = source('src/Commands/Module/SeedCommand.php');
check(str_contains($seedCommand, 'ModuleSeedRun'), 'Module seeders must use a path-aware think-migration runner.');

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "install regression checks passed\n";

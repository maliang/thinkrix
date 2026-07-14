# Modules

Thinkrix can discover modules from multiple paths:

```php
'modules' => [
    'paths' => [
        env('THINKRIX_MODULES_PATH', 'Modules'),
        'app',
    ],
    'backend_path' => env('THINKRIX_BACKEND_PATH', 'app'),
],
```

## Module Structure

```text
Modules/Demo/
├── config.php
├── module.json
├── controller/
├── model/
├── database/
│   ├── migrations/
│   └── seeders/
└── lang/
    ├── zh-cn.php
    └── en-us.php
```

`module.json` serves two roles. Top-level fields such as `name`, `providers`, `middleware`, and `listeners` belong to the Thinkrix runtime. **Only `module.json.trix` is the Trix ecosystem protocol** and is used for publishing, installation, version checks, and adapter matching.

## Market And Project Configuration

All market settings live under `thinkrix.module_market`. The Auth Key uses `TRIX_AUTH_KEY` exclusively; legacy Registry/Auth Key environment names are no longer supported.

The root `trix-project.json` is a publication manifest. After project installation, the only runtime project configuration is the atomically generated `config/trix-project.php`.

Registry requests never follow redirects. Package downloads must have the same scheme, host, and port as `module_market.url`, and staging, copy, and replacement operations reject symbolic links recursively.

Module endpoints are split by responsibility: `ModuleController` handles installed modules, `ModuleMarketController` handles market listing and installation, and `ModulePublishController` handles module/project publication.

## Menus And Permissions

Module menus, permissions, and default data should live in module seeders, so the module remains portable:

```php
Menu::query()->firstOrCreate(
    ['path' => '/demo/orders'],
    [
        'title' => 'module.demo.menu.orders',
        'component' => 'schema',
        'schema_api' => '/demo/orders?action_type=list_ui',
        'permission' => 'demo.orders.view',
    ]
);
```

Menu paths should be final admin route paths. Do not duplicate the module prefix on the frontend.

## Header Items

Modules may declare `header_custom_items` in their config. Thinkrix merges enabled module items into global header items.

Lartrix should use nwidart/laravel-modules for this kind of work; Thinkrix uses its own module loader because it runs on ThinkPHP.

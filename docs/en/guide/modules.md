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

`module.json` provides metadata for the module management page.

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

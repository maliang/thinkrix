# 模块开发

Thinkrix 支持从多个目录发现模块，默认配置：

```php
'modules' => [
    'paths' => [
        env('THINKRIX_MODULES_PATH', 'Modules'),
        'app',
    ],
    'backend_path' => env('THINKRIX_BACKEND_PATH', 'app'),
],
```

## 模块目录

典型模块结构：

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

`module.json` 用于模块管理页展示标题、描述、版本、作者、Logo 等信息。Logo 可通过模块 Logo 接口读取，管理页无需把模块静态资源复制到后台目录。

## 菜单和权限填充

模块菜单、权限和默认数据应写在模块的数据填充中，随模块安装或初始化执行。这样模块迁移到其他项目时，不会丢失后台入口。

```php
use Thinkrix\Models\Menu;
use Thinkrix\Models\Permission;

Menu::query()->firstOrCreate(
    ['path' => '/demo/orders'],
    [
        'title' => 'module.demo.menu.orders',
        'icon' => 'ph:list-checks',
        'component' => 'schema',
        'schema_api' => '/demo/orders?action_type=list_ui',
        'permission' => 'demo.orders.view',
        'sort' => 10,
    ]
);

Permission::query()->firstOrCreate([
    'name' => 'demo.orders.view',
    'guard_name' => 'admin',
]);
```

菜单地址填写最终后台路由路径，不要重复拼接模块名。例如数据库里是 `/finance/recharges`，前端路由也应直接走 `/finance/recharges`。

## 模块导航项

模块可在配置中声明 `header_custom_items`，启用后由 `ModuleLoader` 合并到全局导航栏：

```php
return [
    'header_custom_items' => [
        [
            'icon' => 'mdi:wallet-plus-outline',
            'tooltip' => '待处理充值',
            'badge' => [
                'source' => 'notification',
                'types' => ['mobile.recharge.pending'],
                'mode' => 'count',
                'color' => '#f5222d',
            ],
            'click' => 'route',
            'click_target' => '/finance/recharges',
        ],
    ],
];
```

## 与 Lartrix 的差异

Lartrix 应优先使用 `nwidart/laravel-modules` 的 ServiceProvider、迁移、语言和 Seeder。Thinkrix 没有 Laravel Modules 运行时，因此由 Thinkrix 的模块加载器负责配置、语言和导航项合并。


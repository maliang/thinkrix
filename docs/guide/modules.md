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

`module.json` 同时承载 ThinkPHP 模块运行时声明与 Trix 生态清单。两者边界必须明确：

- 顶层的 `name`、`providers`、`middleware`、`listeners` 等字段由 Thinkrix 模块加载器使用。
- **只有顶层 `trix` 对象属于 Trix 协议**。市场发布、安装、版本校验和 adapter 匹配均只读取 `module.json.trix`。
- 不再支持把 Trix 字段直接放在 `module.json` 顶层，也不支持独立的第二份 Trix manifest。

最小示例：

```json
{
  "name": "Demo",
  "alias": "demo",
  "enabled": false,
  "providers": [],
  "middleware": {"route": [], "global": []},
  "listeners": {},
  "trix": {
    "schema_version": "trix.module.v1",
    "id": "vendor.demo",
    "name": "Demo",
    "description": "示例模块",
    "version": "1.0.0",
    "type": "module",
    "adapter": {
      "language": "php",
      "language_version": ">=8.1",
      "framework": "thinkphp",
      "framework_version": ">=8.0",
      "status": "stable"
    },
    "provides_contracts": [],
    "requires_contracts": [],
    "config": {},
    "security": {"signed": false},
    "menus": [],
    "permissions": []
  }
}
```

标题、描述、版本、作者、Logo 等市场元数据也应放在 `trix` 中。Logo 可通过本地模块 Logo 接口读取，管理页无需把模块静态资源复制到后台目录。

## 模块市场配置

市场相关配置统一放在 `config/thinkrix.php` 的 `module_market` 节点：

```php
'module_market' => [
    'enabled' => env('THINKRIX_MODULE_MARKET_ENABLED', true),
    'url' => env('THINKRIX_MODULE_MARKET_URL', ''),
    'auth_key' => env('TRIX_AUTH_KEY', ''),
    'signature_key' => env('THINKRIX_MODULE_MARKET_SIGNATURE_KEY', ''),
    'timeout' => env('THINKRIX_MODULE_MARKET_TIMEOUT', 30),
    'cache_ttl' => env('THINKRIX_MODULE_MARKET_CACHE_TTL', 3600),
],
```

Auth Key **只使用 `TRIX_AUTH_KEY`**。旧的 `THINKRIX_MODULE_REGISTRY_*`、`TRIX_REGISTRY_*` 或其他 Auth Key 别名不会再读取。命令行的 `--auth-key` 仅用于本次命令覆盖，未传时回落到 `module_market.auth_key`。

## 项目发布与安装

项目清单和项目安装配置是两种不同文件：

- 根目录 `trix-project.json` 是待发布到市场的项目清单，可由 `thinkrix:project-make` 创建或同步，再由 `thinkrix:project-publish` 发布。
- `config/trix-project.php` 是项目安装计划投影出的唯一运行时配置，由 `thinkrix:project-install` 原子生成。不要再维护其他项目安装配置副本。

```bash
# 仅解析计划并写入 config/trix-project.php，不下载模块
php think thinkrix:project-install vendor.project --dry-run

# 写入同一配置，并安装计划中的缺失模块
php think thinkrix:project-install vendor.project --execute
```

`config/trix-project.php` 包含项目 ID、版本、`project_config`、按模块 ID 索引的版本与配置、契约绑定、安装步骤和安装时间。重复安装会替换这一个文件，不会把项目配置散落到模块目录或 `config/thinkrix.php`。

## 下载与文件安全

模块下载和落盘遵循固定安全边界：

1. Registry 请求不跟随 HTTP 重定向，3xx 响应不会被自动追踪。
2. 包下载 URL 必须与 `module_market.url` 的 scheme、host、port 完全同源；跨域、跨协议或跨端口下载会被拒绝，避免把 `TRIX_AUTH_KEY` 带到非信任来源。
3. Zip 预检拒绝 `../`、绝对路径和 Windows 盘符路径，并要求包内存在 `module.json`。
4. 解压到 staging 后重新校验 `module.json.trix` 的模块 ID、版本和 `php/thinkphp` adapter。
5. staging、正式复制和版本替换都会递归检查符号链接；发现软链接立即停止并清理未完成目录，防止越出目标目录读写。

安装器不会自动执行包内脚本。迁移、Seeder、Composer/autoload 等操作必须在审阅清单后由项目维护者明确执行。

## 控制器职责

模块 API 已拆成三个独立控制器，宿主项目覆盖时不要再用一个控制器承接全部职责：

| 配置键 | 默认控制器 | 职责 |
|---|---|---|
| `controllers.module` | `ModuleController` | 本地已安装模块列表、启用、禁用、安装、卸载和 Logo 输出 |
| `controllers.module_market` | `ModuleMarketController` | 市场 UI、模块/项目列表以及市场安装入口 |
| `controllers.module_publish` | `ModulePublishController` | 本地模块发布和项目发布 |

权限也按职责区分为 `module.installed.*`、`module.market.list`、`module.market.install` 和 `module.market.publish`。

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

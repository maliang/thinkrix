# Lartrix Think (Thinkrix)

> ThinkPHP 8 后台管理包，为 [Trix](https://github.com/lartrix/trix) 前端提供完整 API 接口

Thinkrix 是 [Lartrix](https://github.com/lartrix/lartrix)（Laravel 版）的 ThinkPHP 移植版，两者功能同步。前端统一使用 [Trix](https://github.com/lartrix/trix)（Vue 3 + NaiveUI + vschema-ui），后端生态共享。

> 模块生态说明：Trix Module Registry 采用“市场保存整体支持矩阵，包内 manifest 只声明当前 adapter”的模型。Thinkrix 支持 `php/thinkphp` adapter 协议兼容和精选官方模块，但不承诺所有 Laravel 商业模块同步实现。

Registry 安装命令默认只解析模块版本和 ThinkPHP adapter，不下载、不解压、不安装远端包：

```bash
php think thinkrix:module-install official.cms --registry=https://registry.example.com
```

也可以在项目环境变量中配置默认 Registry。命令行未传 `--registry` 时会读取 `THINKRIX_MODULE_REGISTRY_URL`，未传 `--signature-key` 时会读取 `THINKRIX_MODULE_REGISTRY_SIGNATURE_KEY`：

```env
THINKRIX_MODULE_REGISTRY_URL=https://registry.example.com
THINKRIX_MODULE_REGISTRY_SIGNATURE_KEY=your-signature-key
```

如果需要把 adapter 包缓存到本机，可以显式增加 `--download`：

```bash
php think thinkrix:module-install official.cms --registry=https://registry.example.com --download
```

如果 Registry 返回 `signature`，并且本地传入 `--signature-key`，命令还会校验 `hmac-sha256:{base64}` 签名，签名载荷为 adapter checksum 字符串：

```bash
php think thinkrix:module-install official.cms \
  --registry=https://registry.example.com \
  --download \
  --signature-key="${TRIX_REGISTRY_SIGNATURE_KEY}"
```

下载会先校验 `sha256:` checksum，成功后只写入本地缓存目录。缓存包仍需再通过 ThinkPHP/Thinkrix 本地模块流程安装或启用。

缓存完成后 Thinkrix 会进行 zip 预检：检查包内路径是否包含 `../`、绝对路径或 Windows 盘符路径，并确认存在 `module.json`。预检通过后，包会解压到隔离 staging 目录，供后续人工审阅或本地模块流程使用；不会直接移动到正式模块目录，也不会执行包内脚本。

staging 完成后，Thinkrix 会重新读取包内 manifest，核对模块 `id`、`version` 和 `php/thinkphp` adapter 状态是否与 Registry 返回一致。若不一致，流程会停止。

确认 staging 内容后，可以显式复制到本地模块目录：

```bash
php think thinkrix:module-install official.cms \
  --from-stage=/tmp/thinkrix-registry-staging/official.cms-1.0.0 \
  --manifest=official.cms/module.json \
  --version=1.0.0 \
  --target-dir=/app/Modules/OfficialCms
```

该命令只复制文件。目标目录已存在时会拒绝，不会启用模块、不会执行迁移或 Seeder。

复制完成后，命令会扫描模块中的 `composer.json`、Service/Provider、migration、Seeder，并输出人工待办清单和建议命令。这些命令只作为提示，不会自动执行。

如果 manifest 的 `security` 声明包含 `writes_files`、`runs_commands`、`external_network` 或 `requires_secrets`，命令会输出安全审阅提示。第一阶段只提示风险，不自动执行包内脚本，也不自动批准高风险操作。

模块版本更新必须先生成更新计划，再复用 Registry 安装器的下载、校验、staging 和 manifest 复核链路。第一阶段默认只允许升级，不允许降级，也不会自动覆盖正式模块目录。详细流程见 `docs/ecosystem/module-version-update-flow.md`。

确认新版本目录后，推荐先使用专用更新命令做 dry-run：

```bash
php think thinkrix:module-update official.cms \
  --current-dir=/app/Modules/OfficialCms \
  --source-dir=/app/Modules/OfficialCmsNext \
  --manifest=module.json \
  --version=1.1.0 \
  --dry-run \
  --strict-security \
  --audit-log=/app/runtime/trix-module-updates.jsonl
```

`--dry-run` 只输出更新计划，不要求备份目录，也不会移动任何文件。`--strict-security` 会在 manifest 声明写文件、运行命令、访问外网、需要密钥或使用 eval 时直接拒绝。`--audit-log` 会追加写入 JSON Lines 审计记录。确认计划无误后，再显式替换正式目录：

```bash
php think thinkrix:module-update official.cms \
  --current-dir=/app/Modules/OfficialCms \
  --source-dir=/app/Modules/OfficialCmsNext \
  --manifest=module.json \
  --version=1.1.0 \
  --backup-dir=/app/Modules/.backup/OfficialCms-1.0.0 \
  --strict-security \
  --audit-log=/app/runtime/trix-module-updates.jsonl \
  --confirm-replace
```

更新命令会先读取当前目录的 manifest 判断当前版本，默认只允许目标版本更高时继续；随后把旧目录移动到备份目录，再把新目录移动到正式目录。它仍不会自动执行 migration、Seeder、autoload 或包内脚本。

如果确实需要降级，必须显式增加 `--allow-downgrade`。降级前先 dry-run，正式替换时仍必须提供备份目录和确认参数：

```bash
php think thinkrix:module-update official.cms \
  --current-dir=/app/Modules/OfficialCms \
  --source-dir=/app/Modules/OfficialCmsPrev \
  --manifest=module.json \
  --version=1.0.0 \
  --dry-run \
  --allow-downgrade \
  --strict-security \
  --audit-log=/app/runtime/trix-module-updates.jsonl
```

---

## 功能特性

- **用户管理** — 用户 CRUD、角色分配、状态管理、批量删除、Excel 导出
- **角色管理** — 角色 CRUD、权限分配、启用/禁用
- **权限管理** — 树形权限管理、按模块归组
- **菜单管理** — 树形菜单配置、JSON Schema 渲染、图标选择器
- **系统设置** — 标题/Logo/版权等站点配置
- **数据字典** — 字典分组/项管理，前端 Select 选项
- **通知中心** — 消息管理、已读/未读、轮询/WebSocket 实时推送
- **模块管理** — 文件系统模块发现、启停管理、模块市场
- **主题配置** — 亮/暗模式、主题色、布局模式等全量前端配置
- **实时消息** — 支持轮询(polling)和 WebSocket 两种模式，可配置开关和间隔
- **导航栏扩展** — 支持通过配置添加自定义图标按钮，或通过 schema_api 返回任意 UI
- **Token 认证** — Bearer Token，支持多 guard 隔离、过期时间配置
- **RBAC 权限** — 基于角色的访问控制，支持权限继承
- **二级后台** — 模块化开发，支持独立后台（如商户后台）
- **代码生成** — 模块脚手架命令，快速生成 CRUD

---

## 安装

### 环境要求

- PHP 8.1+
- ThinkPHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Composer 2.x

### 步骤

```bash
# 1. 引入包
composer require lartrix/thinkrix

# 2. 发布前端资源（首次安装）
php think thinkrix:publish

# 3. 安装（建表、初始化数据）
php think thinkrix:install

# 4. 前端访问
# 浏览器打开 http://your-domain/admin
# 默认管理员 admin，密码由安装时生成
```

安装命令会自动执行迁移或兜底建表，并创建超级管理员角色、权限、默认菜单和系统设置。

---

## 配置

安装后配置文件位于 `config/thinkrix.php`，支持环境变量覆盖：

| 配置键 | 环境变量 | 默认值 | 说明 |
|--------|---------|--------|------|
| `path` | `THINKRIX_PATH` | `/admin` | 前端入口路径 |
| `api_prefix` | `THINKRIX_API_PREFIX` | `api/admin` | API 路由前缀 |
| `guard` | `THINKRIX_GUARD` | `admin` | 当前 guard 名称 |
| `theme.appTitle` | `THINKRIX_APP_TITLE` | `Thinkrix Admin` | 系统标题 |
| `logo` | `THINKRIX_LOGO` | `/admin/favicon.svg` | 系统 Logo |
| `super_admin_role` | `THINKRIX_SUPER_ADMIN_ROLE` | `super-admin` | 超级管理员角色 |
| `realtime.driver` | `THINKRIX_REALTIME_DRIVER` | `polling` | 实时消息模式（polling/ws） |
| `realtime.polling.interval` | `THINKRIX_REALTIME_POLLING_INTERVAL` | `15000` | 轮询间隔（毫秒） |
| `header.global_search` | `THINKRIX_HEADER_GLOBAL_SEARCH` | `true` | 显示全局搜索 |
| `header.notification` | `THINKRIX_HEADER_NOTIFICATION` | `true` | 显示通知中心 |
| `header.lang_switch` | `THINKRIX_HEADER_LANG_SWITCH` | `true` | 显示语言切换 |

完整配置项参见 `config/thinkrix.php`。

---

## 增加语言

语言种类由 Thinkrix 后端配置提供，不需要修改或重新编译 Trix 前端。例如增加日语：

```php
// config/thinkrix.php
'languages' => [
    'zh-CN' => ['label' => '中文', 'file' => 'zh-cn', 'naive_locale' => 'zh-CN'],
    'en-US' => ['label' => 'English', 'file' => 'en-us', 'naive_locale' => 'en-US'],
    'ja-JP' => ['label' => '日本語', 'file' => 'ja-jp', 'naive_locale' => 'en-US'],
],
```

然后创建项目语言文件 `config/lang/ja-jp.php`。它可以同时包含后端 Schema 文案和 Trix 界面翻译；项目语言会覆盖包内同名键。切换语言后，前端会从 `/translations` 动态加载语言包并缓存，无需增加前端语言模块。

`naive_locale` 控制 Naive UI 日期、分页等内置文案的基础语言，目前支持 `zh-CN` 和 `en-US`；其他语言可以先选择其中一个作为回退。

---

## 命令参考

| 命令 | 说明 |
|------|------|
| `php think thinkrix:install` | 全新安装（建表+初始化数据） |
| `php think thinkrix:publish` | 发布前端静态资源 |
| `php think thinkrix:make-backend` | 创建二级后台模块 |
| `php think thinkrix:remove-backend` | 删除二级后台模块 |
| `php think thinkrix:module-list` | 列出已安装模块 |
| `php think thinkrix:module-make` | 创建新模块 |
| `php think thinkrix:module-enable` | 启用模块 |
| `php think thinkrix:module-disable` | 禁用模块 |
| `php think thinkrix:module-delete` | 删除模块 |
| `php think thinkrix:module-publish-config` | 发布模块配置到项目 |

---

## 导航栏自定义

支持两种方式在右上角添加自定义按钮：

### 1. 简单图标按钮（配置即可）

```php
// config/thinkrix.php
'header' => [
    'custom_items' => [
        [
            'icon'        => 'carbon:notification',
            'tooltip'     => '消息中心',
            'badge_api'   => '/api/custom/unread',  // 返回 { count: N }
            'badge_color' => '#f00',
            'click'       => 'link',
            'click_target'=> 'https://my-app.com',
        ],
    ],
],
```

### 2. 高级自定义（通过 schema API 返回任意 UI）

```php
'header' => [
    'custom_items' => [
        ['schema_api' => '/api/admin/header/my-dropdown'],
    ],
],
```

`schema_api` 接口返回 vschema-ui JSON 节点，支持 NaiveUI 所有组件（Popover、Dropdown、Switch 等）。

---

## 实时消息配置

```php
'realtime' => [
    'enabled' => true,
    'driver' => 'polling',    // polling | ws
    'polling' => [
        'interval' => 15000,  // 毫秒
        'api' => '/notifications/poll',
    ],
    'websocket' => [
        'url' => '',
        'protocol' => 'ws',
    ],
],
```

启用后前端每 `interval` 毫秒调用 `/notifications/poll?since_id=X` 拉取增量消息。
开发者可继承 `RealtimeService` 重写 `getNewMessages()` / `getUnreadCount()` 实现自定义逻辑。

---

## Schema Builder

通过 PHP 代码构建 JSON Schema，由前端 vschema-ui 渲染为 NaiveUI 组件。

### 示例：CRUD 页面

```php
use Thinkrix\Schema\Components\NaiveUI\{Button, Input, Select, SwitchC, Tag, Space, Popconfirm};
use Thinkrix\Schema\Components\Business\{CrudPage, OptForm};
use Thinkrix\Schema\Actions\{SetAction, CallAction, FetchAction, IfAction};

// 表单
$form = OptForm::make('formData')
    ->fields([
        ['用户名', 'username', Input::make()->placeholder('请输入用户名')],
        ['角色', 'roles', Select::make()->multiple(true)->options('{{ roleOptions }}')],
        ['状态', 'status', SwitchC::make(), true],
    ])
    ->buttons([
        Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
        Button::make()->type('primary')->on('click', ['call' => 'handleSubmit'])->text('确定'),
    ]);

// 数据表格
$schema = CrudPage::make('用户管理')
    ->apiPrefix('/users')
    ->columns([
        ['key' => 'id', 'title' => 'ID', 'width' => 80],
        ['key' => 'username', 'title' => '用户名'],
        ['key' => 'status', 'title' => '状态', 'slot' => [
            Tag::make()->props(['type' => "{{ row.status ? 'success' : 'error' }}"])
                ->children(["{{ row.status ? '启用' : '禁用' }}"]),
        ]],
        ['key' => 'actions', 'title' => '操作', 'slot' => [
            Button::make()->type('primary')->text('编辑')
                ->on('click', [SetAction::make('editingId', '{{ row.id }}'), SetAction::make('formVisible', true)]),
        ]],
    ])
    ->defaultPageSize(15)
    ->build();

return success($schema->toArray());
```

支持组件：120+ NaiveUI 组件封装 + 自定义业务组件（CrudPage、OptForm、DataTable 等）。

---

## 文件结构

```
lartrix-think/
├── composer.json
├── config/thinkrix.php         # 配置文件
├── database/migrations/        # 数据库迁移
├── src/
│   ├── ThinkrixService.php     # 服务注册
│   ├── Support/
│   │   └── helpers.php         # success/error 工具函数
│   ├── Exceptions/             # 异常处理（ApiException）
│   ├── Exports/                # Excel 导出（PhpSpreadsheet）
│   ├── Middleware/              # 认证、权限、异常处理中间件
│   ├── Services/               # 业务服务层
│   │   ├── AuthService.php     # Token 认证
│   │   ├── PermissionService.php # RBAC 权限
│   │   ├── RealtimeService.php # 实时消息轮询
│   │   └── ModuleService.php   # 模块管理
│   ├── Models/                 # 数据模型
│   ├── Controllers/            # API 控制器
│   ├── Commands/               # 20+ 控制台命令
│   ├── Schema/                 # Schema Builder
│   │   ├── Actions/            # 8 种 Action 类型
│   │   └── Components/         # 120+ UI 组件封装
│   └── routes.php              # API 路由
├── resources/admin/            # 前端编译产物
├── stubs/                      # 代码生成模板
└── tests/                      # 测试
```

---

## 与 Laravel 版的差异

Thinkrix 与 [Lartrix](https://github.com/lartrix/lartrix)（Laravel 版）功能同步，主要差异：

| 方面 | Lartrix (Laravel) | Thinkrix (ThinkPHP) |
|------|------------------|-------------------|
| 框架 | Laravel 10/11/12 | ThinkPHP 8 |
| Excel 导出 | maatwebsite/excel | PhpSpreadsheet（自带封装） |
| 权限包 | Spatie Permission | 自定义 RBAC |
| Token | Laravel Sanctum | 自定义 Bearer Token |
| 多态关联 | Eloquent morphToMany | BelongsToMany + wherePivot |
| 配置发布 | `php artisan vendor:publish` | `php think thinkrix:publish` |

---

## License

MIT

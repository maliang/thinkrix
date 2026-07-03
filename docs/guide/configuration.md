# 配置

Thinkrix 配置文件为 `config/thinkrix.php`。

## 基础配置

```php
return [
    'path' => env('THINKRIX_PATH', '/admin'),
    'api_prefix' => env('THINKRIX_API_PREFIX', 'api/admin'),
    'guard' => env('THINKRIX_GUARD', 'admin'),

    'locale' => env('THINKRIX_LOCALE', 'zh-CN'),
    'fallback_locale' => 'en-US',
    'languages' => [
        'zh-CN' => ['label' => '中文', 'file' => 'zh-cn', 'naive_locale' => 'zh-CN'],
        'en-US' => ['label' => 'English', 'file' => 'en-us', 'naive_locale' => 'en-US'],
    ],
];
```

`path` 是后台前端路径，`api_prefix` 是后台 API 前缀，`guard` 用于 token 和权限隔离。

## 映射配置

```php
'models' => [
    'user' => \Thinkrix\Models\AdminUser::class,
    'role' => \Thinkrix\Models\Role::class,
    'permission' => \Thinkrix\Models\Permission::class,
    'menu' => \Thinkrix\Models\Menu::class,
    'setting' => \Thinkrix\Models\Setting::class,
],

'controllers' => [
    'auth' => \Thinkrix\Controllers\AuthController::class,
    'setting' => \Thinkrix\Controllers\SettingController::class,
    'system' => \Thinkrix\Controllers\SystemController::class,
    'upload' => \Thinkrix\Controllers\UploadController::class,
],

'tables' => [
    'users' => 'admin_users',
    'menus' => 'admin_menus',
    'settings' => 'admin_settings',
    'roles' => 'roles',
    'permissions' => 'permissions',
],
```

宿主项目可以继承默认模型或控制器，并在这里替换对应类。

## Token

```php
'token' => [
    'table' => 'personal_access_tokens',
    'prefix' => env('THINKRIX_TOKEN_PREFIX', 'thinkrix'),
    'expiration' => env('THINKRIX_TOKEN_EXPIRATION', 86400 * 7),
    'revoke_previous_tokens' => env('THINKRIX_REVOKE_PREVIOUS_TOKENS', false),
],
```

Thinkrix 前端与 Trix 的请求体系保持一致：普通 API、schema `fetch` 和 `NUpload` 上传都会使用同一套 token header。

## 导航栏

```php
'header' => [
    'global_search' => true,
    'notification' => true,
    'full_screen' => true,
    'lang_switch' => true,
    'theme_schema_switch' => true,
    'theme_button' => true,
    'custom_items_position' => 'left',
    'custom_items' => [
        [
            'icon' => 'ph:check-square',
            'tooltip' => '待审核',
            'badge' => [
                'source' => 'notification',
                'types' => ['audit.pending'],
                'mode' => 'count',
                'max' => 99,
                'color' => '#f5222d',
            ],
            'click' => 'route',
            'click_target' => '/audit',
        ],
    ],
],
```

角标统一使用 `badge`，不使用 `badge_api` 或 `badge_color`。`click: route` 表示后台内部跳转，`click: link` 可通过 `target` 控制新标签页或当前页打开。

## 主题

```php
'theme' => [
    'appTitle' => env('THINKRIX_APP_TITLE', 'Thinkrix Admin'),
    'appSubtitle' => env('THINKRIX_APP_SUBTITLE', 'JSON 驱动的后台管理系统'),
    'logo' => env('THINKRIX_LOGO', '/admin/favicon.svg'),
    'footer' => [
        'visible' => false,
    ],
],
```

统一使用 `appTitle`。Logo 地址原样输出，不自动拼接 `/admin`。


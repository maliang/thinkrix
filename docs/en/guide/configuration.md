# Configuration

Thinkrix configuration lives in `config/thinkrix.php`.

## Basic Configuration

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

`path` is the admin frontend path, `api_prefix` is the admin API prefix, and `guard` is used for token and permission isolation.

## Models, Controllers, And Tables

Host projects can extend default models or controllers and override their mappings:

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
```

## Header Items

```php
'header' => [
    'custom_items' => [
        [
            'icon' => 'ph:check-square',
            'tooltip' => 'Pending Reviews',
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

Use `badge`; do not use old `badge_api` or `badge_color` shapes. `click: route` performs an internal admin navigation.

## Theme

```php
'theme' => [
    'appTitle' => env('THINKRIX_APP_TITLE', 'Thinkrix Admin'),
    'appSubtitle' => env('THINKRIX_APP_SUBTITLE', 'JSON driven admin system'),
    'logo' => env('THINKRIX_LOGO', '/admin/favicon.svg'),
    'footer' => [
        'visible' => false,
    ],
],
```

Use `appTitle` consistently. Logo URLs are rendered as-is and are not prefixed with `/admin`.

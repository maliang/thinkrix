# 主题与站点设置

Thinkrix 主题配置保存在 `config/thinkrix.php` 和数据库设置中。

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

## 字段约定

统一使用 `appTitle`。旧的 `app_title` 只作为读取历史数据的兜底，不应再写入新配置或新系统设置。

Logo 地址按原样输出，不自动拼接后台路径。默认兜底为：

```text
/admin/favicon.svg
```

## 系统设置页

系统设置页的 Logo 使用图片上传组件，上传成功后直接回显。保存后会执行：

```php
[
    'call' => '$methods.$theme.updateSite',
    'args' => ['{{ formData.appTitle }}', '{{ formData.logo }}'],
]
```

因此菜单顶部标题、Logo 和浏览器标题会立即变化，不需要刷新页面。

## 底部

默认底部不显示：

```php
'footer' => [
    'visible' => false,
],
```

如果需要显示版权信息，开启 footer 并配置 `copyright`。


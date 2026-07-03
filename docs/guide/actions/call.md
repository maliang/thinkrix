# CallAction

`CallAction` 用于调用 schema 方法、Trix 内置方法或自定义方法。

## 内置方法

```php
CallAction::make('$message.success', ['保存成功']);
CallAction::make('$notification.create', [['title' => '提示']]);
CallAction::make('$nav.push', ['/system/user']);
CallAction::make('$window.print');
```

`CallAction` 会自动把以下前缀补成 `$methods.`：

- `$message.`
- `$dialog.`
- `$notification.`
- `$loadingBar.`
- `$nav.`
- `$tab.`
- `$window.`
- `$download`

## 调用页面方法

```php
Card::make()
    ->methods([
        'reload' => [CallAction::make('loadData')],
    ]);
```

未带内置前缀的方法名会按原样输出，用于调用当前 schema 中定义的方法。

## 站点更新

```php
CallAction::make('$theme.updateSite', [
    '{{ formData.appTitle }}',
    '{{ formData.logo }}',
]);
```

系统设置保存后可用它即时更新菜单顶部标题和 Logo。


# 反馈组件

反馈组件用于提示、确认、加载和空状态。

## 消息提示

推荐通过 Action 调用 Trix 注入的方法：

```php
CallAction::make('$message.success', ['保存成功']);
CallAction::make('$message.error', ['保存失败']);
```

`CallAction` 会自动把 `$message.`、`$notification.`、`$dialog.`、`$nav.` 等内置前缀补成 `$methods.` 调用。

## Popconfirm

```php
Popconfirm::make()
    ->props([
        'positiveText' => '确定',
        'negativeText' => '取消',
    ])
    ->on('positive-click', FetchAction::make('/users/1')->delete())
    ->children(['确定删除？']);
```

## Empty 与 Result

```php
EmptyState::make()->props(['description' => '暂无数据']);

Result::make()
    ->props(['status' => '404', 'title' => '页面不存在']);
```

## Loading

列表页通常使用 `loading` 状态绑定到表格：

```php
DataTable::make()->props([
    'loading' => '{{ loading }}',
]);
```

并在请求前后用 `SetAction` 控制。


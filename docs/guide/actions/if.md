# IfAction

`IfAction` 用于根据前端表达式选择不同动作。

```php
IfAction::make('formData.status === 1')
    ->then([CallAction::make('$message.success', ['已启用'])])
    ->else([CallAction::make('$message.warning', ['已禁用'])]);
```

## 嵌套使用

```php
IfAction::make('!Array.isArray($response.data)')
    ->then([
        CallAction::make('$message.error', ['数据格式错误']),
        SetAction::make('tableData', []),
    ])
    ->else([
        SetAction::make('tableData', '{{ $response.data }}'),
    ]);
```

## 注意

条件表达式在浏览器端执行，可访问当前 schema 状态、事件上下文、`$response`、`$error` 等运行时变量。


# 其他 Actions

除常用的 `SetAction`、`CallAction`、`FetchAction`、`IfAction` 外，Thinkrix 还提供一些辅助动作。

## CopyAction

复制文本到剪贴板：

```php
CopyAction::make('{{ apiKey }}')
    ->then([CallAction::make('$message.success', ['复制成功'])]);
```

## EmitAction

触发自定义事件：

```php
EmitAction::make('refresh-list', ['source' => 'toolbar']);
```

## ScriptAction

执行脚本片段。仅在无法用声明式 Action 表达时使用：

```php
ScriptAction::make('state.count = (state.count || 0) + 1');
```

## WebSocketAction

用于连接、发送或关闭 WebSocket。通知实时消息通常走 Thinkrix/Trix 内置 realtime 服务，业务页面确实需要自定义连接时再使用。

## 使用建议

优先选择声明式动作；只有遇到复杂计算、临时桥接第三方脚本时才使用 `ScriptAction`。


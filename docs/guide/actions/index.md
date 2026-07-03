# Action 概述

Actions 是 schema 中的行为描述，位于 `Thinkrix\Schema\Actions`。组件事件、生命周期、方法、请求回调都可以使用 Action。

## 常用 Action

- `SetAction`：设置状态值
- `CallAction`：调用前端方法或 schema 方法
- `FetchAction`：发起 API 请求
- `IfAction`：条件分支
- `CopyAction`：复制内容
- `EmitAction`：触发事件
- `ScriptAction`：执行脚本片段
- `WebSocketAction`：WebSocket 操作

## 示例

```php
Button::make()
    ->text('保存')
    ->on('click', [
        SetAction::make('loading', true),
        FetchAction::make('/settings')->post()->body('{{ formData }}')
            ->then([CallAction::make('$message.success', ['保存成功'])])
            ->finally([SetAction::make('loading', false)]),
    ]);
```

事件动作会在前端由 vschema-ui 执行。


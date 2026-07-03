# Other Actions

Thinkrix also provides helper actions besides `SetAction`, `CallAction`, `FetchAction`, and `IfAction`.

## CopyAction

```php
CopyAction::make('{{ apiKey }}')
    ->then([CallAction::make('$message.success', ['Copied'])]);
```

## EmitAction

```php
EmitAction::make('refresh-list', ['source' => 'toolbar']);
```

## ScriptAction

Use script snippets only when declarative actions are not enough:

```php
ScriptAction::make('state.count = (state.count || 0) + 1');
```

## WebSocketAction

Use for custom WebSocket operations. Notification realtime normally uses the built-in Thinkrix/Trix realtime service.


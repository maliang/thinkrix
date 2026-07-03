# Action Overview

Actions describe behavior in schema. They live in `Thinkrix\Schema\Actions` and can be used in events, lifecycle hooks, methods, and request callbacks.

## Common Actions

- `SetAction`: set state values
- `CallAction`: call frontend or schema methods
- `FetchAction`: send API requests
- `IfAction`: conditional branches
- `CopyAction`: copy text
- `EmitAction`: emit custom events
- `ScriptAction`: run script snippets
- `WebSocketAction`: WebSocket operations

## Example

```php
Button::make()
    ->text('Save')
    ->on('click', [
        SetAction::make('loading', true),
        FetchAction::make('/settings')->post()->body('{{ formData }}')
            ->then([CallAction::make('$message.success', ['Saved'])])
            ->finally([SetAction::make('loading', false)]),
    ]);
```

Actions are executed in the browser by vschema-ui.


# IfAction

`IfAction` chooses actions based on a frontend expression.

```php
IfAction::make('formData.status === 1')
    ->then([CallAction::make('$message.success', ['Enabled'])])
    ->else([CallAction::make('$message.warning', ['Disabled'])]);
```

## Nested Usage

```php
IfAction::make('!Array.isArray($response.data)')
    ->then([
        CallAction::make('$message.error', ['Invalid data format']),
        SetAction::make('tableData', []),
    ])
    ->else([
        SetAction::make('tableData', '{{ $response.data }}'),
    ]);
```

The condition runs in the browser and may access schema state, event context, `$response`, and `$error`.


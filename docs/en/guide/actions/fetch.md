# FetchAction

`FetchAction` sends API requests from schema.

## GET

```php
FetchAction::make('/users')
    ->params(['page' => '{{ pagination.page }}'])
    ->then([SetAction::make('tableData', '{{ $response.data.list }}')]);
```

## POST

```php
FetchAction::make('/settings')
    ->post()
    ->body('{{ formData }}')
    ->then([CallAction::make('$message.success', ['Saved'])])
    ->catch([CallAction::make('$message.error', ['{{ $error.message || "Failed" }}'])]);
```

## Callbacks

- `then()`: success
- `catch()`: failure
- `finally()`: always runs

Trix handles baseURL, response format, and token authentication.


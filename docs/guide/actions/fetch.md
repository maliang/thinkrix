# FetchAction

`FetchAction` 用于在 schema 中发起 API 请求。

## GET 请求

```php
FetchAction::make('/users')
    ->params(['page' => '{{ pagination.page }}'])
    ->then([SetAction::make('tableData', '{{ $response.data.list }}')]);
```

## POST 请求

```php
FetchAction::make('/settings')
    ->post()
    ->body('{{ formData }}')
    ->then([CallAction::make('$message.success', ['保存成功'])])
    ->catch([CallAction::make('$message.error', ['{{ $error.message || "保存失败" }}'])]);
```

## 回调

- `then()`：请求成功
- `catch()`：请求失败
- `finally()`：无论成功失败都会执行

```php
FetchAction::make('/users')
    ->then([SetAction::make('list', '{{ $response.data }}')])
    ->catch([CallAction::make('$message.error', ['加载失败'])])
    ->finally([SetAction::make('loading', false)]);
```

Trix 会统一处理 baseURL、响应格式和 token 认证。


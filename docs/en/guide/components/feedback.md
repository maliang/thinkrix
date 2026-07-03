# Feedback Components

Feedback components handle messages, confirmations, loading, and empty states.

## Messages

```php
CallAction::make('$message.success', ['Saved']);
CallAction::make('$message.error', ['Failed']);
```

`CallAction` automatically normalizes `$message.`, `$notification.`, `$dialog.`, and `$nav.` into `$methods.` calls.

## Popconfirm

```php
Popconfirm::make()
    ->props([
        'positiveText' => 'Confirm',
        'negativeText' => 'Cancel',
    ])
    ->on('positive-click', FetchAction::make('/users/1')->delete())
    ->children(['Delete this record?']);
```

## Empty And Result

```php
EmptyState::make()->props(['description' => 'No data']);

Result::make()
    ->props(['status' => '404', 'title' => 'Not Found']);
```

## Loading

```php
DataTable::make()->props([
    'loading' => '{{ loading }}',
]);
```

Use `SetAction` before and after requests to control loading state.


# CallAction

`CallAction` calls schema methods, Trix built-in methods, or custom methods.

## Built-in Methods

```php
CallAction::make('$message.success', ['Saved']);
CallAction::make('$notification.create', [['title' => 'Notice']]);
CallAction::make('$nav.push', ['/system/user']);
CallAction::make('$window.print');
```

`CallAction` automatically prefixes built-in namespaces with `$methods.`.

## Page Methods

```php
Card::make()
    ->methods([
        'reload' => [CallAction::make('loadData')],
    ]);
```

Names without built-in prefixes are emitted as-is and call methods defined in the current schema.

## Site Update

```php
CallAction::make('$theme.updateSite', [
    '{{ formData.appTitle }}',
    '{{ formData.logo }}',
]);
```

Use this after saving site settings to update header title and logo immediately.


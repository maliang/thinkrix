# Schema UI

Thinkrix builds Trix-compatible JSON schema with PHP components. Common pages use `CrudPage`, `DataTable`, `OptForm`, and Naive UI wrappers.

## Controller Responses

Non-UI APIs return ordinary arrays. UI APIs return schema arrays:

```php
public function listUi(): array
{
    return success(
        CrudPage::make('User Management')
            ->apiPrefix('/users')
            ->build()
    );
}
```

ThinkPHP responses must not pass raw PHP objects where rendered content is expected; schema components should be converted to arrays.

## Single Image Upload

Use `OneImgUp` for settings logos or similar fields:

```php
OneImgUp::make('formData.logo')
    ->action('/api/admin/upload/image')
    ->width(96)
    ->height(96);
```

Trix replaces schema `NUpload` with an authenticated upload component, so each schema does not need to manually attach token headers.

## Schema Methods

After saving site settings, update the title and logo immediately:

```php
[
    'call' => '$methods.$theme.updateSite',
    'args' => ['{{ formData.appTitle }}', '{{ formData.logo }}'],
]
```

Built-in methods such as `$message`, `$notification`, and `$theme` should be called through the `$methods` namespace.

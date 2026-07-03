# Theme And Site Settings

Thinkrix theme defaults live in `config/thinkrix.php` and can be overridden by stored settings.

```php
'theme' => [
    'appTitle' => env('THINKRIX_APP_TITLE', 'Thinkrix Admin'),
    'appSubtitle' => env('THINKRIX_APP_SUBTITLE', 'JSON driven admin system'),
    'logo' => env('THINKRIX_LOGO', '/admin/favicon.svg'),
    'footer' => [
        'visible' => false,
    ],
],
```

## Field Names

Use `appTitle` consistently. The legacy `app_title` key is only a fallback for reading old data.

Logo URLs are rendered as-is. The default fallback is:

```text
/admin/favicon.svg
```

## Settings Page

The settings page uses image upload for the logo. After saving, it calls:

```php
[
    'call' => '$methods.$theme.updateSite',
    'args' => ['{{ formData.appTitle }}', '{{ formData.logo }}'],
]
```

The menu header title, logo, and browser title update immediately without a page refresh.

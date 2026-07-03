# Basic Components

Basic components mostly come from `Thinkrix\Schema\Components\NaiveUI` and `Custom`.

## Text And Icons

```php
Text::make()->strong()->children(['System Status']);
SvgIcon::make('ph:gear')->props(['class' => 'text-xl text-primary']);
```

`SvgIcon` size is controlled by CSS `font-size`; `Icon` uses `width` and `height`.

## Buttons

```php
Button::make()
    ->type('primary')
    ->text('Submit')
    ->on('click', ['call' => '$methods.$message.success', 'args' => ['ok']]);
```

Common props:

- `type`: `primary`, `info`, `success`, `warning`, `error`
- `loading`
- `disabled`
- `size`

## Tags And Badges

```php
Tag::make()->type('success')->children(['Enabled']);

Badge::make()->props([
    'value' => 12,
    'max' => 99,
    'color' => '#f5222d',
]);
```

Menu and header badges should use the backend `badge` protocol, not old `badge_api` configuration.


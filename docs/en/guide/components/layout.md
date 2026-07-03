# Layout Components

Layout components organize page structure: `Card`, `Space`, `Flex`, `Grid`, `Layout`, `Tabs`, `Modal`, and `Drawer`.

## Card

```php
Card::make()
    ->props([
        'title' => 'System Info',
        'style' => ['height' => '100%'],
    ])
    ->children([$content]);
```

## Space And Flex

```php
Space::make()
    ->props(['vertical' => true, 'size' => 'large'])
    ->children([$searchForm, $toolbar, $table]);

Flex::make()
    ->props(['justify' => 'end'])
    ->children([$pagination]);
```

## Modal And Drawer

```php
Modal::make()
    ->props(['show' => '{{ formVisible }}', 'title' => 'Edit'])
    ->on('update:show', ['set' => 'formVisible', 'value' => false])
    ->children([$form]);
```

For CRUD pages, prefer `CrudPage::modal()` and `CrudPage::drawer()` to reduce duplicated state handling.


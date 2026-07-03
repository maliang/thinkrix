# 布局组件

布局组件用于组织页面结构，常用 `Card`、`Space`、`Flex`、`Grid`、`Layout`、`Tabs`、`Modal`、`Drawer`。

## Card

```php
Card::make()
    ->props([
        'title' => '系统信息',
        'style' => ['height' => '100%'],
    ])
    ->children([$content]);
```

## Space 与 Flex

```php
Space::make()
    ->props(['vertical' => true, 'size' => 'large'])
    ->children([$searchForm, $toolbar, $table]);

Flex::make()
    ->props(['justify' => 'end'])
    ->children([$pagination]);
```

## Modal 与 Drawer

`CrudPage` 内置 `modal()` 和 `drawer()`，普通页面也可以直接使用 Naive UI 封装：

```php
Modal::make()
    ->props(['show' => '{{ formVisible }}', 'title' => '编辑'])
    ->on('update:show', ['set' => 'formVisible', 'value' => false])
    ->children([$form]);
```

复杂表单、列表页弹窗优先使用 `CrudPage` 的封装，减少重复状态管理。


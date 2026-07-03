# 数据展示组件

数据展示常用 `DataTable`、`Descriptions`、`Statistic`、`Tag`、`Avatar` 等组件。

## DataTable

```php
DataTable::make()->props([
    'data' => '{{ tableData }}',
    'columns' => [
        ['key' => 'id', 'title' => 'ID'],
        ['key' => 'name', 'title' => '名称'],
    ],
    'rowKey' => '{{ row => row.id }}',
]);
```

## 表格插槽

```php
[
    'key' => 'status',
    'title' => '状态',
    'slot' => [
        Tag::make()
            ->type('{{ slotData.row.status ? "success" : "error" }}')
            ->children(['{{ slotData.row.status ? "启用" : "禁用" }}']),
    ],
]
```

插槽表达式在前端执行，翻译后的固定文案建议在 PHP 侧通过 `__t()` 写入。

## 树表格

`CrudPage` 支持树表格：

```php
CrudPage::make('菜单管理')
    ->apiPrefix('/menus')
    ->tree('children', true)
    ->columns($columns)
    ->build();
```

树表格会关闭分页，并使用 `children` 字段作为子级数据。


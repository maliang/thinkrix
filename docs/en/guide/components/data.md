# Data Display

Common data components include `DataTable`, `Descriptions`, `Statistic`, `Tag`, and `Avatar`.

## DataTable

```php
DataTable::make()->props([
    'data' => '{{ tableData }}',
    'columns' => [
        ['key' => 'id', 'title' => 'ID'],
        ['key' => 'name', 'title' => 'Name'],
    ],
    'rowKey' => '{{ row => row.id }}',
]);
```

## Table Slots

```php
[
    'key' => 'status',
    'title' => 'Status',
    'slot' => [
        Tag::make()
            ->type('{{ slotData.row.status ? "success" : "error" }}')
            ->children(['{{ slotData.row.status ? "Enabled" : "Disabled" }}']),
    ],
]
```

Slot expressions run in the browser. Prefer translating fixed labels in PHP with `__t()`.

## Tree Tables

```php
CrudPage::make('Menu Management')
    ->apiPrefix('/menus')
    ->tree('children', true)
    ->columns($columns)
    ->build();
```

Tree mode disables pagination and uses `children` as the child row key.


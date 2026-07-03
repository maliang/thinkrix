# SetAction

`SetAction` 用于修改 schema 状态。

## 单个赋值

```php
SetAction::make('formVisible', true);
SetAction::make('formData.name', '{{ slotData.row.name }}');
```

输出：

```json
{ "set": "formVisible", "value": true }
```

## 批量赋值

```php
SetAction::batch([
    'formVisible' => true,
    'editingId' => '{{ slotData.row.id }}',
    'formData' => '{{ { ...slotData.row } }}',
]);
```

批量模式会展开成多个 `set` 动作。

## 注意

表达式字符串如 `{{ slotData.row.id }}` 在前端运行。后端不要尝试在 PHP 中求值这类表达式。


# Data Dictionary

Thinkrix includes a dictionary management page for enum-like data such as statuses, categories, and business types.

## Structure

- Group: `code`, `name`, `description`, `is_system`
- Item: `code`, `label`, `value`, `sort`, `is_enabled`

System groups are intended for built-in data and should not be deleted by business code.

## Page Entry

Default schema endpoint:

```text
/dicts/groups?action_type=list_ui
```

## Usage In Business Pages

Controllers can load dictionary items and generate `Select` options:

```php
Select::make()->props([
    'options' => $options,
    'clearable' => true,
]);
```

When showing dictionary values in tables, convert values to labels on the backend or map them in column slots.


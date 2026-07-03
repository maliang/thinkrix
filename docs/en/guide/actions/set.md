# SetAction

`SetAction` changes schema state.

## Single Assignment

```php
SetAction::make('formVisible', true);
SetAction::make('formData.name', '{{ slotData.row.name }}');
```

Output:

```json
{ "set": "formVisible", "value": true }
```

## Batch Assignment

```php
SetAction::batch([
    'formVisible' => true,
    'editingId' => '{{ slotData.row.id }}',
    'formData' => '{{ { ...slotData.row } }}',
]);
```

Batch mode expands to multiple `set` actions.

Expressions such as <code v-pre>{{ slotData.row.id }}</code> run in the browser, not in PHP.

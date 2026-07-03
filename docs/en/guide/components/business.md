# Business Components

Business components live in `Thinkrix\Schema\Components\Business` and wrap common admin patterns.

## CrudPage

```php
CrudPage::make('User Management')
    ->apiPrefix('/users')
    ->search([
        ['Keyword', 'keyword', Input::make()->props(['clearable' => true])],
    ])
    ->toolbarLeft([
        Button::make()->type('primary')->text('Create'),
    ])
    ->toolbarRight(['columnSelector'])
    ->columns([
        ['key' => 'id', 'title' => 'ID'],
        ['key' => 'username', 'title' => 'Username'],
    ])
    ->build();
```

## OptForm

`OptForm` organizes save forms and is often used in modals, drawers, or settings pages.

## OneImgUp

```php
OneImgUp::make('formData.logo')
    ->action('/api/admin/upload/image')
    ->placeholderText('Upload Logo');
```

## Editors

```php
MarkdownEditor::make()
    ->model('formData.content')
    ->uploadUrl('/api/admin/upload/image');
```


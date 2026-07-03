# Form Components

Thinkrix forms are usually built with `Form`, `FormItem`, and input components. Use `OptForm` for more complex save forms.

## Basic Form

```php
Form::make()
    ->props(['model' => '{{ formData }}'])
    ->children([
        FormItem::make()
            ->label('Name')
            ->children([
                Input::make()->model('formData.name')->props(['placeholder' => 'Name']),
            ]),
    ]);
```

## OptForm

```php
OptForm::make()
    ->model('formData')
    ->children([
        FormItem::make()->label('Title')->children([
            Input::make()->model('formData.title'),
        ]),
    ]);
```

## Date And Time

Trix registers `modelAdapters` for `NTimePicker` and `NDatePicker`. String and empty values are bound through `formatted-value`, avoiding `Invalid time value`.

```php
TimePicker::make()
    ->model('formData.open_time')
    ->props(['format' => 'HH:mm:ss', 'valueFormat' => 'HH:mm:ss']);
```

## Single Image Upload

```php
OneImgUp::make('formData.logo')
    ->action('/api/admin/upload/image')
    ->width(96)
    ->height(96);
```

`OneImgUp` uses Trix authenticated upload behavior, so upload requests automatically include the token.


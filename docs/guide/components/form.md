# 表单组件

Thinkrix 表单通常由 `Form`、`FormItem` 和输入组件组成，复杂表单推荐使用业务组件 `OptForm`。

## 基础表单

```php
use Thinkrix\Schema\Components\NaiveUI\Form;
use Thinkrix\Schema\Components\NaiveUI\FormItem;
use Thinkrix\Schema\Components\NaiveUI\Input;

Form::make()
    ->props(['model' => '{{ formData }}'])
    ->children([
        FormItem::make()
            ->label('名称')
            ->children([
                Input::make()->model('formData.name')->props(['placeholder' => '请输入名称']),
            ]),
    ]);
```

## OptForm

`OptForm` 用于保存型表单，可配合 `FetchAction` 和 `$message`：

```php
OptForm::make()
    ->model('formData')
    ->children([
        FormItem::make()->label('标题')->children([
            Input::make()->model('formData.title'),
        ]),
    ]);
```

## 日期时间

Trix 已为 `NTimePicker` / `NDatePicker` 注册 `modelAdapters`。当模型值是字符串或空值时，会自动使用 `formatted-value`，避免 `Invalid time value`。

```php
TimePicker::make()
    ->model('formData.open_time')
    ->props(['format' => 'HH:mm:ss', 'valueFormat' => 'HH:mm:ss']);
```

## 单图上传

```php
OneImgUp::make('formData.logo')
    ->action('/api/admin/upload/image')
    ->width(96)
    ->height(96);
```

`OneImgUp` 底层使用 Trix 的认证上传组件，上传时会自动带 token。


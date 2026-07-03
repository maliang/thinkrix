# 业务组件

业务组件封装常见后台页面模式，位于 `Thinkrix\Schema\Components\Business`。

## CrudPage

`CrudPage` 用于快速构建列表、搜索、工具栏、分页、弹窗和抽屉：

```php
CrudPage::make('用户管理')
    ->apiPrefix('/users')
    ->search([
        ['关键词', 'keyword', Input::make()->props(['clearable' => true])],
    ])
    ->toolbarLeft([
        Button::make()->type('primary')->text('新增'),
    ])
    ->toolbarRight(['columnSelector'])
    ->columns([
        ['key' => 'id', 'title' => 'ID'],
        ['key' => 'username', 'title' => '用户名'],
    ])
    ->build();
```

## OptForm

`OptForm` 用于表单内容组织，常与 `Modal`、`Drawer` 或设置页搭配。

## OneImgUp

`OneImgUp` 是单图上传组合组件，适合 Logo、头像、封面图：

```php
OneImgUp::make('formData.logo')
    ->action('/api/admin/upload/image')
    ->placeholderText('上传 Logo');
```

## 编辑器

`MarkdownEditor` 和 `RichEditor` 可配置上传地址与语言：

```php
MarkdownEditor::make()
    ->model('formData.content')
    ->uploadUrl('/api/admin/upload/image');
```


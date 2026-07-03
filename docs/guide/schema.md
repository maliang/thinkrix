# Schema UI

Thinkrix 用 PHP 组件构建 Trix 可渲染的 JSON schema。常见页面由 `CrudPage`、`DataTable`、`OptForm` 和 Naive UI 组件组合完成。

## 控制器返回

非界面 API 返回普通数组，界面 API 返回 schema 数组：

```php
public function index(): array
{
    return success($this->service->paginate());
}

public function listUi(): array
{
    return success(
        CrudPage::make('用户管理')
            ->apiPrefix('/users')
            ->build()
    );
}
```

ThinkPHP 响应层不能直接输出未序列化的复杂对象；schema 组件最终应调用 `build()` 或由组件体系转换为数组。

## 常用组件

```php
OptForm::make()
    ->model('formData')
    ->children([
        FormItem::make()
            ->label(__t('system.setting.form.appTitle'))
            ->children([
                Input::make()->model('formData.appTitle'),
            ]),
    ]);
```

## 单图上传

系统设置 Logo 推荐使用 `OneImgUp`：

```php
OneImgUp::make('formData.logo')
    ->action('/api/admin/upload/image')
    ->width(96)
    ->height(96);
```

Trix 会把 schema 中的 `NUpload` 替换为带认证头的上传组件，因此不需要在每个上传 schema 中手写 token。

## Schema 方法

保存系统设置后可立即更新站点标题和 Logo：

```php
[
    'call' => '$methods.$theme.updateSite',
    'args' => ['{{ formData.appTitle }}', '{{ formData.logo }}'],
]
```

内置 `$message`、`$notification`、`$theme` 等方法需要通过 `$methods` 命名空间调用。


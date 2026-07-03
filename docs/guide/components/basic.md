# 基础组件

基础组件主要来自 `Thinkrix\Schema\Components\NaiveUI` 和 `Custom` 目录。

## 文本与图标

```php
use Thinkrix\Schema\Components\NaiveUI\Text;
use Thinkrix\Schema\Components\Custom\SvgIcon;

Text::make()->strong()->children(['系统状态']);
SvgIcon::make('ph:gear')->props(['class' => 'text-xl text-primary']);
```

`SvgIcon` 的大小通过 CSS `font-size` 控制；`Icon` 组件使用 `width` / `height`。

## 按钮

```php
Button::make()
    ->type('primary')
    ->text('提交')
    ->on('click', ['call' => '$methods.$message.success', 'args' => ['ok']]);
```

常用 props：

- `type`：`primary`、`info`、`success`、`warning`、`error`
- `loading`
- `disabled`
- `size`

## 标签和角标

```php
Tag::make()->type('success')->children(['启用']);

Badge::make()->props([
    'value' => 12,
    'max' => 99,
    'color' => '#f5222d',
]);
```

菜单和导航栏角标应使用后端配置中的 `badge` 协议，不要使用旧的 `badge_api`。


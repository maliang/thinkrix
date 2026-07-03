# 组件概述

Thinkrix 的 PHP Schema 组件位于 `src/Schema/Components`，用于生成 Trix/vschema-ui 可渲染的 JSON Schema。

## 组件分类

- `NaiveUI`：Naive UI 组件封装，如 `Button`、`Input`、`DataTable`、`Card`。
- `Business`：业务组合组件，如 `CrudPage`、`OptForm`、`OneImgUp`、`MarkdownEditor`。
- `Common`：后台布局公共组件，如 `HeaderNotification`、`HeaderCustomItem`、`ThemeButton`。
- `Custom`：Trix 自定义组件，如 `SvgIcon`、`Icon`、`Html`、`VueECharts`。

## 基础用法

```php
use Thinkrix\Schema\Components\NaiveUI\Card;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Actions\CallAction;

return Card::make()
    ->props(['title' => '示例'])
    ->children([
        Button::make()
            ->type('primary')
            ->text('保存')
            ->on('click', CallAction::make('$message.success', ['保存成功'])),
    ])
    ->toArray();
```

所有组件最终都应通过 `toArray()` 或业务组件的 `build()` 输出数组。


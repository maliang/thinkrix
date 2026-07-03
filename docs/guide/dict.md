# 数据字典

Thinkrix 内置字典管理页面，用于维护枚举型数据，例如状态、分类、业务类型。

## 数据结构

通常包含字典分组和字典项：

- 分组：`code`、`name`、`description`、`is_system`
- 字典项：`code`、`label`、`value`、`sort`、`is_enabled`

系统分组可用于内置状态，不建议业务直接删除。

## 页面入口

默认菜单：

```text
系统管理 / 字典管理
```

默认 schema 接口：

```text
/dicts/groups?action_type=list_ui
```

## 在业务页面中使用

业务控制器可以读取字典项后生成 `Select` 选项：

```php
Select::make()->props([
    'options' => $options,
    'clearable' => true,
]);
```

字典值进入表格时，建议在后端转换为 label，或在列 slot 中按选项映射显示。


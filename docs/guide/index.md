# 介绍

Thinkrix 是 Lartrix 的 ThinkPHP 移植版，复用 Trix 前端和 vschema-ui 渲染体系，为 ThinkPHP 项目提供后台管理、权限、菜单、模块、系统设置、多语言和实时消息能力。

## 文档地图

- [安装](./installation.md)
- [配置](./configuration.md)
- [组件概述](./components/index.md)
- [Action 概述](./actions/index.md)
- [模块开发](./modules.md)
- [多语言](./i18n.md)
- [数据字典](./dict.md)
- [Schema UI](./schema.md)
- [通知与实时消息](./notifications.md)
- [主题与站点设置](./theme.md)
- [自定义组件](./custom-components.md)

## 与 Lartrix 的关系

Thinkrix 和 Lartrix 尽量保持前端协议一致：

- 统一使用 Trix 的 JSON schema、`CrudPage`、`DataTable`、`OptForm`、`OneImgUp` 等页面表达方式。
- 统一使用 `appTitle`、`logo`、`realtime.behaviors`、`header.custom_items`、`badge` 等配置名。
- 统一多语言接口：`/api/admin/translations`、`/api/admin/locale`。
- 统一通知轮询返回：`unread_count`、`unread_count_by_type`、`messages`。

差异主要来自框架生态：

- Lartrix 使用 Laravel、Sanctum、Spatie Permission、nwidart/laravel-modules。
- Thinkrix 使用 ThinkPHP 的配置、路由、中间件、模型和命令体系，模块加载由 Thinkrix 自己适配。

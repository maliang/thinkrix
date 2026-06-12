# Lartrix Think (Thinkrix)

> ThinkPHP 8 后台管理包，为 [Trix](https://github.com/lartrix/trix) 前端提供 API 接口

## 概述

Thinkrix 是从 [Lartrix](https://github.com/lartrix/lartrix) 移植到 ThinkPHP 8 的后台管理包。目前提供以下核心能力，部分高级功能与 Laravel 版本仍有差异：

- 用户、角色、权限与菜单管理
- 系统设置、数据字典与通知中心
- 文件系统模块发现和启停管理
- 自定义 Token 认证
- PHP Schema Builder 与 NaiveUI 组件封装
- 二级后台模块创建和删除命令

## 安装

```bash
composer require lartrix/lartrix-think
```

## 使用

```bash
# 发布资源
php think thinkrix:publish

# 安装
php think thinkrix:install

# 创建二级后台
php think thinkrix:make-backend Merchant --path=/merchant --api-prefix=api/merchant --title=商户管理系统

# 删除二级后台
php think thinkrix:remove-backend Merchant
```

安装命令会尝试执行包内 ThinkPHP 迁移；若迁移命令不可用或执行失败，会使用内置 SQL 建齐运行所需的数据表。

## 技术栈

- PHP 8.1+, ThinkPHP 8.0+
- PhpSpreadsheet（导出）
- 自定义 Token 认证
- 自定义 RBAC 权限

## 文件结构

```
lartrix-think/
├── composer.json
├── config/thinkrix.php         # 配置文件
├── database/migrations/        # 数据库迁移
├── src/
│   ├── ThinkrixService.php     # 服务注册
│   ├── Support/helpers.php     # success/error 函数
│   ├── Exceptions/             # 异常处理
│   ├── Exports/                # 导出功能
│   ├── Middleware/              # 中间件
│   ├── Services/               # 业务服务
│   ├── Models/                 # 数据模型
│   ├── Controllers/            # API 控制器
│   ├── Commands/               # 控制台命令
│   ├── Schema/                 # Schema Builder
│   │   ├── Actions/            # 8 种 Action 类型
│   │   └── Components/         # 120+ UI 组件
│   └── routes.php              # 路由配置
├── resources/admin/            # 前端资源
└── stubs/                      # 代码生成模板
```

## Schema Builder 示例

```php
use Thinkrix\Schema\Components\NaiveUI\{Button, Input, Select, SwitchC, Tag, Space, Popconfirm};
use Thinkrix\Schema\Components\Business\{CrudPage, OptForm};
use Thinkrix\Schema\Actions\{SetAction, CallAction, FetchAction, IfAction};

// 构建 CRUD 页面
$schema = CrudPage::make('用户管理')
    ->apiPrefix('/users')
    ->columns([...])
    ->search([['关键词', 'keyword', Input::make()->placeholder('搜索')]])
    ->toolbarLeft([Button::make()->type('primary')->text('新增')])
    ->build();

return success($schema);
```

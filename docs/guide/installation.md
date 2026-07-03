# 安装

## 环境要求

- PHP 8.1+
- ThinkPHP 8
- MySQL 或 MariaDB
- Composer

## 安装包

```bash
composer require lartrix/lartrix-think
```

如果是在本地 monorepo 开发，宿主项目通常通过 Composer path repository 引用 `lartrix-think`。

## 发布资源

```bash
php think thinkrix:publish
```

发布内容包括：

- 后台前端资源到 `public/admin`
- 默认配置文件到宿主项目
- 语言文件和必要静态资源

修改 Trix 前端源码后，应先在 `trix` 中构建，再发布到 Thinkrix；不要直接改 `lartrix-think/resources/admin` 或 `public/admin` 的构建产物。

## 初始化数据库

```bash
php think thinkrix:install
```

安装命令会执行：

1. 发布前端资源。
2. 执行数据库迁移；迁移失败时会使用完整建表兜底。
3. 初始化用户、角色、权限、菜单、系统设置、通知分类等基础数据。

安装完成后访问 `THINKRIX_PATH`，默认是 `/admin`。

## 常见问题

### 外键迁移失败

部分 MySQL 环境或旧表结构可能导致外键创建失败。Thinkrix 安装命令会在迁移失败时走兜底建表逻辑，重点确认最终基础表是否创建成功。

### 控制器不存在 app\\controller\\Api

确认 Thinkrix 路由已加载，且请求路径包含配置的 `api_prefix`，默认是 `/api/admin`。多语言接口应访问：

- `GET /api/admin/translations`
- `POST /api/admin/locale`


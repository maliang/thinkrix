<?php

namespace Thinkrix\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\Db;
use Thinkrix\Models\AdminUser;
use Thinkrix\Models\Menu;
use Thinkrix\Models\Permission;
use Thinkrix\Models\Role;
use Thinkrix\Models\Setting;
use Thinkrix\Models\NotificationCategory;
use think\migration\command\migrate\Run as MigrationRun;

class InstallCommand extends Command
{
    protected function configure()
    {
        $this->setName('thinkrix:install')
            ->setDescription('安装 Thinkrix 后台管理系统')
            ->addOption('username', null, Option::VALUE_OPTIONAL, '超级管理员用户名')
            ->addOption('password', null, Option::VALUE_OPTIONAL, '超级管理员密码');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->info('开始安装 Thinkrix...');
        $output->writeln('');

        // 1. 发布前端资源
        $output->info('1. 发布前端资源到 public/admin...');
        $this->publishAssets($output);
        $output->info('   前端资源发布完成。');

        // 2. 发布配置文件
        $output->info('2. 发布配置文件到 config...');
        $this->publishConfig($output);
        $output->info('   配置发布完成。');

        // 3. 执行数据库迁移
        $output->info('3. 执行数据库迁移...');
        if ($this->runMigrations($output)) {
            $output->info('   迁移完成。');
        } else {
            $output->writeln('<comment>   迁移未执行，将使用完整建表兜底。</comment>');
        }

        // 4. 创建基础表（如果不使用迁移）
        $output->info('4. 初始化基础数据表...');
        $this->initBaseTables($output);
        $output->info('   基础表初始化完成。');

        // 5. 创建超级管理员角色
        $output->info('5. 创建超级管理员角色...');
        $role = $this->createSuperAdminRole($output);
        $output->info('   角色创建完成。');

        // 6. 创建基础权限
        $output->info('6. 创建基础权限...');
        $this->createBasePermissions($output);
        $output->info('   权限创建完成。');

        // 7. 初始化系统设置
        $output->info('7. 初始化系统设置...');
        $this->initializeSettings($output);
        $output->info('   系统设置初始化完成。');

        // 8. 创建默认菜单
        $output->info('8. 创建默认菜单...');
        $this->createDefaultMenus($output);
        $output->info('   默认菜单创建完成。');

        // 9. 初始化通知分类
        $output->info('9. 初始化通知分类...');
        $this->initNotificationCategories($output);
        $output->info('   通知分类初始化完成。');

        // 10. 创建管理员账户
        $output->info('10. 创建超级管理员账户...');
        [$username, $password] = $this->resolveAdminCredentials($input, $output);
        $admin = $this->createSuperAdmin($role, $username, $password, $output);

        // 输出安装摘要
        $output->writeln('');
        $output->info('========================================');
        $output->info('       Thinkrix 安装完成！');
        $output->info('========================================');
        $output->writeln('');
        $output->writeln("管理员用户名: {$admin->username}");

        return 0;
    }

    protected function publishAssets(Output $output): void
    {
        $sourceDir = __DIR__ . '/../../resources/admin';
        $rootPath = $this->app->getRootPath();
        $targetDir = $rootPath . 'public' . DIRECTORY_SEPARATOR . 'admin';

        if (is_dir($targetDir)) {
            $output->writeln('   前端资源目录已存在，跳过。如需强制覆盖，请手动删除目录后重试。');
            return;
        }

        $this->copyDir($sourceDir, $targetDir);
    }

    protected function publishConfig(Output $output): void
    {
        $sourceFile = __DIR__ . '/../../config/thinkrix.php';
        $targetFile = $this->app->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'thinkrix.php';

        if (file_exists($targetFile)) {
            $output->writeln('   配置文件已存在，跳过。');
            return;
        }

        $targetDir = dirname($targetFile);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (copy($sourceFile, $targetFile)) {
            $output->info('   配置发布完成: config/thinkrix.php');
        } else {
            $output->writeln('<error>   配置发布失败。</error>');
        }
    }

    protected function runMigrations(Output $output): bool
    {
        try {
            $migrationPath = realpath(__DIR__ . '/../../database/migrations');
            $command = new class($migrationPath) extends MigrationRun {
                public function __construct(protected string $migrationPath)
                {
                    parent::__construct();
                }
                protected function getPath(): string
                {
                    return $this->migrationPath;
                }
            };
            $command->setApp($this->app);
            $command->setConsole($this->getConsole());
            $command->run(new Input([$command->getName()]), $output);
            return true;
        } catch (\Throwable $e) {
            $output->writeln('<comment>   迁移执行失败: ' . $e->getMessage() . '</comment>');
            return false;
        }
    }

    protected function initBaseTables(Output $output): void
    {
        $sqls = [
            // 管理员用户表
            "CREATE TABLE IF NOT EXISTS `admin_users` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `username` varchar(20) NOT NULL COMMENT '用户名',
                `password` varchar(255) NOT NULL COMMENT '密码',
                `nickname` varchar(20) DEFAULT NULL COMMENT '昵称',
                `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
                `email` varchar(255) DEFAULT NULL COMMENT '邮箱',
                `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
                `status` varchar(10) DEFAULT '1' COMMENT '状态',
                `remark` varchar(255) DEFAULT NULL COMMENT '备注',
                `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP',
                `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
                `deleted_at` datetime DEFAULT NULL COMMENT '软删除',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员用户表';",

            // 权限表
            "CREATE TABLE IF NOT EXISTS `permissions` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `parent_id` int UNSIGNED DEFAULT NULL COMMENT '父级权限ID',
                `name` varchar(255) NOT NULL COMMENT '权限标识',
                `title` varchar(255) DEFAULT NULL COMMENT '权限名称',
                `guard_name` varchar(50) NOT NULL DEFAULT 'admin' COMMENT 'guard名称',
                `module` varchar(255) DEFAULT NULL COMMENT '所属模块',
                `description` text COMMENT '描述',
                `sort` int DEFAULT '0' COMMENT '排序',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_name_guard` (`name`,`guard_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限表';",

            // 角色表
            "CREATE TABLE IF NOT EXISTS `roles` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL COMMENT '角色标识',
                `title` varchar(255) DEFAULT NULL COMMENT '角色名称',
                `guard_name` varchar(50) NOT NULL DEFAULT 'admin' COMMENT 'guard名称',
                `description` text COMMENT '描述',
                `status` tinyint(1) DEFAULT '1' COMMENT '状态',
                `is_system` tinyint(1) DEFAULT '0' COMMENT '是否系统内置',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_name_guard` (`name`,`guard_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';",

            // 角色-权限关联表
            "CREATE TABLE IF NOT EXISTS `role_has_permissions` (
                `role_id` int UNSIGNED NOT NULL,
                `permission_id` int UNSIGNED NOT NULL,
                PRIMARY KEY (`role_id`,`permission_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';",

            // 模型-角色关联表
            "CREATE TABLE IF NOT EXISTS `model_has_roles` (
                `role_id` int UNSIGNED NOT NULL,
                `model_type` varchar(255) NOT NULL,
                `model_id` int UNSIGNED NOT NULL,
                PRIMARY KEY (`role_id`,`model_type`,`model_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模型角色关联表';",

            // 菜单表
            "CREATE TABLE IF NOT EXISTS `admin_menus` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `guard_name` varchar(50) NOT NULL DEFAULT 'admin' COMMENT '所属guard',
                `parent_id` int UNSIGNED DEFAULT NULL COMMENT '父级菜单ID',
                `name` varchar(255) NOT NULL COMMENT '路由名称',
                `path` varchar(255) NOT NULL COMMENT '路由路径',
                `component` varchar(255) DEFAULT NULL COMMENT '组件路径',
                `redirect` varchar(255) DEFAULT NULL COMMENT '重定向',
                `title` varchar(255) DEFAULT NULL COMMENT '菜单标题',
                `icon` varchar(255) DEFAULT NULL COMMENT '菜单图标',
                `order` int DEFAULT '0' COMMENT '排序',
                `hide_in_menu` tinyint(1) DEFAULT '0' COMMENT '是否隐藏',
                `keep_alive` tinyint(1) DEFAULT '0' COMMENT '是否缓存',
                `permissions` text COMMENT '所需权限（JSON数组）',
                `use_json_renderer` tinyint(1) DEFAULT '0' COMMENT '使用JSON渲染',
                `schema_source` varchar(255) DEFAULT NULL COMMENT 'Schema来源',
                `layout_type` varchar(50) DEFAULT NULL COMMENT '布局类型',
                `open_type` varchar(50) DEFAULT NULL COMMENT '打开方式',
                `href` varchar(255) DEFAULT NULL COMMENT '外部链接',
                `is_default_after_login` tinyint(1) DEFAULT '0' COMMENT '登录后默认页',
                `fixed_index_in_tab` int DEFAULT NULL COMMENT '固定标签索引',
                `requires_auth` tinyint(1) DEFAULT '1' COMMENT '需要认证',
                `active_menu` varchar(255) DEFAULT NULL COMMENT '激活的菜单',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_name_guard` (`name`,`guard_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单表';",

            // 设置表
            "CREATE TABLE IF NOT EXISTS `admin_settings` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `group` varchar(100) DEFAULT 'general' COMMENT '分组',
                `key` varchar(100) NOT NULL COMMENT '键名',
                `title` varchar(255) DEFAULT NULL COMMENT '标题',
                `type` varchar(50) DEFAULT 'string' COMMENT '类型',
                `value` text COMMENT '值',
                `default_value` text COMMENT '默认值',
                `description` text COMMENT '描述',
                `sort` int DEFAULT '0' COMMENT '排序',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_key` (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表';",

            // 模块表
            "CREATE TABLE IF NOT EXISTS `modules` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL COMMENT '模块名称',
                `title` varchar(255) DEFAULT NULL COMMENT '模块标题',
                `description` text COMMENT '描述',
                `version` varchar(20) DEFAULT '1.0.0' COMMENT '版本',
                `author` varchar(100) DEFAULT NULL COMMENT '作者',
                `website` varchar(255) DEFAULT NULL COMMENT '网址',
                `logo` varchar(255) DEFAULT NULL COMMENT 'LOGO',
                `enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
                `config` text COMMENT '配置（JSON）',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='模块表';",

            // Token 表（替代 Laravel Sanctum）
            "CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `tokenable_type` varchar(255) NOT NULL,
                `tokenable_id` int UNSIGNED NOT NULL,
                `name` varchar(255) NOT NULL COMMENT 'Token名称',
                `token` varchar(64) NOT NULL COMMENT 'Token值（SHA256）',
                `abilities` text COMMENT '权限（JSON数组）',
                `last_used_at` datetime DEFAULT NULL,
                `expires_at` datetime DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_token` (`token`),
                KEY `idx_tokenable` (`tokenable_type`,`tokenable_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问令牌表';",

            "CREATE TABLE IF NOT EXISTS `dict_groups` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `code` varchar(50) NOT NULL COMMENT '分组编码',
                `name` varchar(100) NOT NULL COMMENT '分组名称',
                `description` varchar(255) DEFAULT NULL COMMENT '描述',
                `is_system` tinyint(1) DEFAULT '0' COMMENT '是否系统内置',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典分组表';",

            "CREATE TABLE IF NOT EXISTS `dict_items` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `group_id` int UNSIGNED NOT NULL COMMENT '分组ID',
                `code` varchar(50) NOT NULL COMMENT '项编码',
                `label` varchar(100) NOT NULL COMMENT '显示文本',
                `value` varchar(100) NOT NULL COMMENT '存储值',
                `sort` int DEFAULT '0' COMMENT '排序',
                `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
                `extra` text COMMENT '额外数据（JSON）',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_group_code` (`group_id`,`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典项表';",

            "CREATE TABLE IF NOT EXISTS `notification_categories` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL COMMENT '分类名称',
                `key` varchar(50) NOT NULL COMMENT '分类标识',
                `icon` varchar(100) DEFAULT NULL COMMENT '图标',
                `color` varchar(50) DEFAULT NULL COMMENT '颜色',
                `sort` int DEFAULT '0' COMMENT '排序',
                `message_types` text COMMENT '消息类型（JSON数组）',
                `guard_name` varchar(50) NOT NULL DEFAULT 'admin' COMMENT '所属guard',
                `enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_key_guard` (`key`,`guard_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知分类表';",

            "CREATE TABLE IF NOT EXISTS `notification_messages` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL COMMENT '消息标题',
                `content` text COMMENT '消息内容',
                `type` varchar(50) NOT NULL DEFAULT 'system' COMMENT '消息类型',
                `category_key` varchar(50) NOT NULL COMMENT '分类标识',
                `guard_name` varchar(50) NOT NULL DEFAULT 'admin' COMMENT '所属guard',
                `user_id` int UNSIGNED DEFAULT NULL COMMENT '接收用户ID',
                `from_user_id` int UNSIGNED DEFAULT NULL COMMENT '发送用户ID',
                `from_guard` varchar(50) DEFAULT NULL COMMENT '发送者guard',
                `target_guards` text COMMENT '目标guards（JSON数组）',
                `is_read` tinyint(1) DEFAULT '0' COMMENT '是否已读',
                `read_at` datetime DEFAULT NULL COMMENT '阅读时间',
                `extra` text COMMENT '额外数据（JSON）',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_is_read` (`is_read`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知消息表';",
        ];

        foreach ($sqls as $sql) {
            try {
                $this->app->db->execute($sql);
            } catch (\Exception $e) {
                $output->writeln('<comment>   建表警告: ' . $e->getMessage() . '</comment>');
            }
        }
    }

    protected function createSuperAdminRole(Output $output): Role
    {
        $roleName = config('thinkrix.super_admin_role', 'super-admin');
        $existing = Role::where('name', $roleName)->where('guard_name', 'admin')->find();
        if ($existing) {
            $output->writeln('   超级管理员角色已存在，跳过。');
            return $existing;
        }
        return Role::create([
            'name' => $roleName, 'guard_name' => 'admin',
            'title' => '超级管理员', 'description' => '拥有所有权限的超级管理员',
            'status' => true, 'is_system' => true,
        ]);
    }

    protected function createBasePermissions(Output $output): void
    {
        $permissionTree = [
            ['name' => 'system', 'title' => '系统管理', 'module' => 'system', 'sort' => 9999, 'children' => [
                ['name' => 'system.user', 'title' => '用户管理', 'sort' => 1, 'children' => [
                    ['name' => 'system.user.list', 'title' => '用户列表', 'sort' => 1],
                    ['name' => 'system.user.create', 'title' => '创建用户', 'sort' => 2],
                    ['name' => 'system.user.update', 'title' => '编辑用户', 'sort' => 3],
                    ['name' => 'system.user.delete', 'title' => '删除用户', 'sort' => 4],
                    ['name' => 'system.user.status', 'title' => '修改状态', 'sort' => 5],
                    ['name' => 'system.user.password', 'title' => '重置密码', 'sort' => 6],
                ]],
                ['name' => 'system.role', 'title' => '角色管理', 'sort' => 2, 'children' => [
                    ['name' => 'system.role.list', 'title' => '角色列表', 'sort' => 1],
                    ['name' => 'system.role.create', 'title' => '创建角色', 'sort' => 2],
                    ['name' => 'system.role.update', 'title' => '编辑角色', 'sort' => 3],
                    ['name' => 'system.role.delete', 'title' => '删除角色', 'sort' => 4],
                    ['name' => 'system.role.permissions', 'title' => '分配权限', 'sort' => 5],
                ]],
                ['name' => 'system.permission', 'title' => '权限管理', 'sort' => 3, 'children' => [
                    ['name' => 'system.permission.list', 'title' => '权限列表', 'sort' => 1],
                    ['name' => 'system.permission.create', 'title' => '创建权限', 'sort' => 2],
                    ['name' => 'system.permission.update', 'title' => '编辑权限', 'sort' => 3],
                    ['name' => 'system.permission.delete', 'title' => '删除权限', 'sort' => 4],
                ]],
                ['name' => 'system.menu', 'title' => '菜单管理', 'sort' => 4, 'children' => [
                    ['name' => 'system.menu.list', 'title' => '菜单列表', 'sort' => 1],
                    ['name' => 'system.menu.create', 'title' => '创建菜单', 'sort' => 2],
                    ['name' => 'system.menu.update', 'title' => '编辑菜单', 'sort' => 3],
                    ['name' => 'system.menu.delete', 'title' => '删除菜单', 'sort' => 4],
                    ['name' => 'system.menu.sort', 'title' => '菜单排序', 'sort' => 5],
                ]],
                ['name' => 'system.setting', 'title' => '系统设置', 'sort' => 5, 'children' => [
                    ['name' => 'system.setting.list', 'title' => '设置列表', 'sort' => 1],
                    ['name' => 'system.setting.update', 'title' => '更新设置', 'sort' => 2],
                ]],
                ['name' => 'system.dict', 'title' => '字典管理', 'sort' => 6, 'children' => [
                    ['name' => 'system.dict.list', 'title' => '字典列表', 'sort' => 1],
                    ['name' => 'system.dict.create', 'title' => '创建字典', 'sort' => 2],
                    ['name' => 'system.dict.update', 'title' => '编辑字典', 'sort' => 3],
                    ['name' => 'system.dict.delete', 'title' => '删除字典', 'sort' => 4],
                ]],
            ]],
            ['name' => 'module', 'title' => '模块管理', 'module' => 'system', 'sort' => 9980, 'children' => [
                ['name' => 'module.installed', 'title' => '已装模块', 'sort' => 1, 'children' => [
                    ['name' => 'module.installed.list', 'title' => '模块列表', 'sort' => 1],
                    ['name' => 'module.installed.enable', 'title' => '启用模块', 'sort' => 2],
                    ['name' => 'module.installed.disable', 'title' => '禁用模块', 'sort' => 3],
                ]],
                ['name' => 'module.market', 'title' => '模块市场', 'sort' => 2, 'children' => [
                    ['name' => 'module.market.list', 'title' => '市场列表', 'sort' => 1],
                    ['name' => 'module.market.install', 'title' => '安装模块', 'sort' => 2],
                ]],
            ]],
        ];

        $this->createPermissionsRecursive($permissionTree);
    }

    protected function createPermissionsRecursive(array $permissions, ?int $parentId = null, ?string $module = null): void
    {
        foreach ($permissions as $permission) {
            $children = $permission['children'] ?? [];
            unset($permission['children']);
            $permission['module'] = $permission['module'] ?? $module;
            $permission['parent_id'] = $parentId;
            $permission['guard_name'] = 'admin';

            $exists = Permission::where('name', $permission['name'])->where('guard_name', 'admin')->find();
            if (!$exists) {
                Permission::create($permission);
            }

            if (!empty($children)) {
                $created = Permission::where('name', $permission['name'])->where('guard_name', 'admin')->find();
                $this->createPermissionsRecursive($children, $created->id, $permission['module']);
            }
        }
    }

    protected function initializeSettings(Output $output): void
    {
        Setting::setValue('theme', [
            'appTitle' => config('thinkrix.app_title', 'Thinkrix Admin'),
            'logo' => config('thinkrix.logo', '/favicon.svg'),
            'themeScheme' => 'light',
            'grayscale' => false,
            'colourWeakness' => false,
            'recommendColor' => false,
            'themeColor' => '#646cff',
            'themeRadius' => 6,
            'otherColor' => [
                'info' => '#2080f0',
                'success' => '#52c41a',
                'warning' => '#faad14',
                'error' => '#f5222d',
            ],
            'isInfoFollowPrimary' => true,
            'layout' => [
                'mode' => 'vertical',
                'scrollMode' => 'content',
            ],
            'page' => [
                'animate' => true,
                'animateMode' => 'fade-slide',
            ],
            'header' => [
                'height' => 56,
                'inverted' => false,
                'breadcrumb' => [
                    'visible' => true,
                    'showIcon' => true,
                ],
                'multilingual' => [
                    'visible' => true,
                ],
                'globalSearch' => [
                    'visible' => true,
                ],
            ],
            'tab' => [
                'visible' => true,
                'cache' => true,
                'height' => 44,
                'mode' => 'chrome',
                'closeTabByMiddleClick' => false,
            ],
            'fixedHeaderAndTab' => true,
            'sider' => [
                'inverted' => false,
                'width' => 220,
                'collapsedWidth' => 64,
                'mixWidth' => 90,
                'mixCollapsedWidth' => 64,
                'mixChildMenuWidth' => 200,
                'mixChildMenuBgColor' => '#ffffff',
                'autoSelectFirstMenu' => false,
            ],
            'footer' => [
                'visible' => true,
                'height' => 48,
                'fixed' => false,
                'right' => true,
            ],
            'watermark' => [
                'visible' => false,
                'text' => config('thinkrix.app_title', 'Thinkrix Admin'),
                'enableUserName' => false,
                'enableTime' => false,
                'timeFormat' => 'YYYY-MM-DD HH:mm',
            ],
            'tokens' => [
                'light' => [
                    'colors' => [
                        'container' => 'rgb(255, 255, 255)',
                        'layout' => 'rgb(247, 250, 252)',
                        'inverted' => 'rgb(0, 20, 40)',
                        'base-text' => 'rgb(31, 31, 31)',
                    ],
                    'boxShadow' => [
                        'header' => '0 1px 2px rgb(0, 21, 41, 0.08)',
                        'sider' => '2px 0 8px 0 rgb(29, 35, 41, 0.05)',
                        'tab' => '0 1px 2px rgb(0, 21, 41, 0.08)',
                    ],
                ],
                'dark' => [
                    'colors' => [
                        'container' => 'rgb(28, 28, 28)',
                        'layout' => 'rgb(18, 18, 18)',
                        'base-text' => 'rgb(224, 224, 224)',
                    ],
                ],
            ],
        ]);
    }

    protected function createDefaultMenus(Output $output): void
    {
        $menus = [
            ['name' => 'home', 'path' => '/home', 'title' => '首页', 'icon' => 'mdi:home', 'order' => 1, 'use_json_renderer' => true, 'schema_source' => '/dashboard'],
            ['name' => 'system', 'path' => '/system', 'redirect' => '/system/user', 'title' => '系统管理', 'icon' => 'mdi:cog', 'order' => 9999, 'children' => [
                ['name' => 'system.user', 'path' => 'user', 'title' => '成员管理', 'icon' => 'mdi:account-group', 'order' => 1, 'use_json_renderer' => true, 'schema_source' => '/users?action_type=list_ui'],
                ['name' => 'system.role', 'path' => 'role', 'title' => '角色管理', 'icon' => 'mdi:account-key', 'order' => 2, 'use_json_renderer' => true, 'schema_source' => '/roles?action_type=list_ui'],
                ['name' => 'system.menu', 'path' => 'menu', 'title' => '菜单管理', 'icon' => 'mdi:menu', 'order' => 3, 'use_json_renderer' => true, 'schema_source' => '/menus?action_type=list_ui'],
                ['name' => 'system.permission', 'path' => 'permission', 'title' => '权限管理', 'icon' => 'mdi:shield-key', 'order' => 4, 'use_json_renderer' => true, 'schema_source' => '/permissions?action_type=list_ui'],
                ['name' => 'system.setting', 'path' => 'setting', 'title' => '系统设置', 'icon' => 'mdi:cog-outline', 'order' => 5, 'use_json_renderer' => true, 'schema_source' => '/settings?action_type=form_ui'],
                ['name' => 'system.dict', 'path' => 'dict', 'title' => '字典管理', 'icon' => 'mdi:book-open', 'order' => 6, 'use_json_renderer' => true, 'schema_source' => '/dicts/groups?action_type=list_ui'],
                ['name' => 'system.module', 'path' => 'module', 'title' => '模块管理', 'icon' => 'mdi:puzzle', 'order' => 7, 'use_json_renderer' => true, 'schema_source' => '/modules?action_type=installed_ui'],
            ]],
        ];

        foreach ($menus as $menu) {
            $this->createMenu($menu);
        }
    }

    protected function createMenu(array $data, ?int $parentId = null): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $menuData = [
            'guard_name' => config('thinkrix.guard', 'admin'),
            'parent_id' => $parentId,
            'name' => $data['name'],
            'path' => $data['path'],
            'title' => $data['title'] ?? null,
            'icon' => $data['icon'] ?? null,
            'order' => $data['order'] ?? 0,
            'hide_in_menu' => $data['hide_in_menu'] ?? false,
            'keep_alive' => $data['keep_alive'] ?? false,
            'requires_auth' => $data['requires_auth'] ?? true,
            'use_json_renderer' => $data['use_json_renderer'] ?? false,
            'schema_source' => $data['schema_source'] ?? null,
            'redirect' => $data['redirect'] ?? null,
        ];

        $exists = Menu::where('name', $menuData['name'])->where('guard_name', $menuData['guard_name'])->find();
        if (!$exists) {
            $menu = Menu::create($menuData);
            foreach ($children as $child) {
                $this->createMenu($child, $menu->id);
            }
        }
    }

    protected function initNotificationCategories(Output $output): void
    {
        $guard = config('thinkrix.guard', 'admin');
        $categories = config('thinkrix.notification.default_categories', []);

        foreach ($categories as $cat) {
            if ($cat['key'] === 'all') continue; // '全部' 是动态选项
            $exists = NotificationCategory::where('key', $cat['key'])->where('guard_name', $guard)->find();
            if (!$exists) {
                NotificationCategory::create([
                    'name' => $cat['name'],
                    'key' => $cat['key'],
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'sort' => $cat['sort'],
                    'message_types' => $cat['message_types'],
                    'guard_name' => $guard,
                    'enabled' => true,
                ]);
            }
        }
    }

    protected function resolveAdminCredentials(Input $input, Output $output): array
    {
        $username = $input->getOption('username');
        if (!$username) {
            $username = $input->isInteractive() ? $output->ask($input, '管理员用户名', 'admin') : 'admin';
        }

        $password = $input->getOption('password');
        if (!$password && $input->isInteractive()) {
            $password = $output->askHidden($input, '管理员密码（至少 6 位）', function ($value) {
                if (!is_string($value) || strlen($value) < 6) {
                    throw new \InvalidArgumentException('管理员密码至少需要 6 位。');
                }
                return $value;
            });
        }
        if (!$password) {
            $password = bin2hex(random_bytes(12));
        }
        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('管理员密码至少需要 6 位。');
        }

        return [(string) $username, (string) $password];
    }

    protected function createSuperAdmin(Role $role, string $username, string $password, Output $output): AdminUser
    {
        $existing = AdminUser::where('username', $username)->find();

        if ($existing) {
            $output->writeln('   管理员账户已存在，跳过。');
            return $existing;
        }

        $admin = AdminUser::create([
            'username' => $username,
            'password' => $password,
            'nickname' => '超级管理员',
            'status' => '1',
        ]);

        // 分配角色
        $this->app->db->name('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => AdminUser::class,
            'model_id' => $admin->id,
        ]);

        $output->writeln("   管理员用户名: {$username}");
        $output->writeln("   一次性管理员密码: {$password}");

        return $admin;
    }

    protected function copyDir(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($items as $item) {
            $target = $dest . DIRECTORY_SEPARATOR . $items->getSubPathname();
            if ($item->isDir()) {
                mkdir($target, 0755, true);
            } else {
                copy($item, $target);
            }
        }
    }
}

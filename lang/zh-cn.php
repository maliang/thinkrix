<?php
/**
 * 中文语言包
 *
 * 命名约定：{模块}.{类别}.{字段}
 */
return [
    // ==================== 通用 UI ====================
    'ui' => [
        'button' => [
            'cancel' => '取消',
            'confirm' => '确定',
            'delete' => '删除',
            'edit' => '编辑',
            'create' => '新增',
            'save' => '保存',
            'search' => '搜索',
            'reset' => '重置',
            'back' => '返回',
            'yes' => '是',
            'no' => '否',
            'visit' => '访问',
            'refresh' => '刷新页面',
        ],
        'tag' => [
            'enabled' => '启用',
            'disabled' => '禁用',
            'system_yes' => '是',
            'system_no' => '否',
        ],
    ],

    // ==================== 系统 ====================
    'system' => [
        'dashboard' => [
            'title' => '仪表盘',
            'welcome' => '欢迎使用 Thinkrix 后台管理系统',
            'welcome_desc' => '基于 ThinkPHP 8 和 Trix 前端的后台管理解决方案',
        ],
        'setting' => [
            'title' => '系统设置',
            'form' => [
                'app_title' => '系统名称',
                'logo_url' => 'Logo 地址',
                'copyright' => '版权信息',
            ],
            'placeholder' => [
                'app_title' => '请输入系统名称',
                'logo_url' => '请输入 Logo 地址',
                'copyright' => '请输入版权信息',
            ],
        ],
        'login' => [
            'title' => '登 录',
            'reset_password' => '重置密码',
            'get_code' => '获取验证码',
            'form' => [
                'username' => '用户名',
                'password' => '密码',
                'phone' => '手机号',
                'code' => '验证码',
                'new_password' => '新密码',
                'confirm_pwd' => '确认密码',
            ],
            'placeholder' => [
                'username' => '请输入用户名',
                'password' => '请输入密码',
                'phone' => '请输入手机号',
                'code' => '请输入验证码',
                'new_pwd' => '请输入新密码',
                'confirm' => '请再次输入密码',
            ],
            'message' => [
                'username_required' => '请输入用户名',
                'password_required' => '请输入密码',
                'password_min' => '密码长度不能少于6位',
                'phone_required' => '请输入手机号',
                'phone_invalid' => '请输入正确的手机号',
                'code_required' => '请输入验证码',
                'code_len' => '验证码为6位数字',
                'fill_all' => '请填写完整信息',
                'pwd_mismatch' => '两次输入的密码不一致',
            ],
        ],
        'error' => [
            '403' => '403',
            '403_desc' => '抱歉，您没有权限访问此页面',
            '404' => '404',
            '404_desc' => '抱歉，您访问的页面不存在',
            '500' => '500',
            '500_desc' => '抱歉，服务器出现错误，请稍后再试',
        ],
        'button' => [
            'save' => '保存设置',
            'back_home' => '返回首页',
            'back_prev' => '返回上一页',
            'refresh' => '刷新页面',
        ],
        'column' => [
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ],
        'message' => [
            'config_saved' => '保存成功',
            'locale_saved' => '语言设置已保存',
            'theme_saved' => '主题配置已保存',
            'builtin_not_deletable' => '系统内置分组不允许删除',
        ],
    ],

    // ==================== 认证 ====================
    'auth' => [
        'message' => [
            'failed' => '用户名或密码错误',
            'login_ok' => '登录成功',
            'logout_ok' => '登出成功',
            'refresh_ok' => '刷新成功',
            'revoke_ok' => '撤销成功',
            'token_not_found' => 'Token 不存在',
            'password_incorrect' => '当前密码不正确',
            'password_mismatch' => '两次输入的密码不一致',
            'password_changed' => '密码修改成功，请重新登录',
            'password_reset_ok' => '密码重置成功',
        ],
    ],

    // ==================== 模块管理 ====================
    'module' => [
        'installed' => ['title' => '已安装模块'],
        'market' => [
            'title' => '模块商城',
            'button' => '模块商城',
            'coming_soon' => '敬请期待',
            'coming_soon_desc' => '模块市场正在开发中，即将上线...',
        ],
        'column' => [
            'logo' => 'Logo',
            'name' => '模块名称',
            'version' => '版本',
            'description' => '描述',
            'author' => '作者',
            'website' => '网址',
            'status' => '状态',
            'actions' => '操作',
        ],
        'button' => [
            'install' => '安装',
            'uninstall' => '卸载',
            'enable' => '启用',
            'disable' => '禁用',
            'visit' => '访问',
        ],
        'tag' => [
            'installed' => '已启用',
            'not_installed' => '已禁用',
        ],
        'confirm' => [
            'uninstall' => '确定卸载该模块？将删除菜单和权限，并回滚数据库迁移。',
        ],
        'message' => [
            'installed' => '安装成功',
            'install_failed' => '安装失败',
            'uninstalled' => '卸载成功',
            'uninstall_failed' => '卸载失败',
            'enabled' => '启用成功',
            'enable_failed' => '启用失败',
            'disabled' => '禁用成功',
            'disable_failed' => '禁用失败',
            'not_found' => '模块不存在',
        ],
    ],

    // ==================== 用户管理 ====================
    'user' => [
        'title' => '成员管理',
        'column' => [
            'username' => '用户名',
            'nickname' => '昵称',
            'avatar' => '头像',
            'email' => '邮箱',
            'phone' => '手机号',
            'status' => '状态',
            'roles' => '角色',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'actions' => '操作',
        ],
        'button' => [
            'create' => '新增',
            'edit' => '编辑',
            'delete' => '删除',
            'reset_password' => '重置密码',
            'save' => '保存',
            'confirm_modify' => '确认修改',
        ],
        'form' => [
            'username' => '用户名',
            'password' => '密码',
            'nickname' => '昵称',
            'email' => '邮箱',
            'phone' => '手机号',
            'roles' => '角色',
            'status' => '状态',
            'new_password' => '新密码',
        ],
        'placeholder' => [
            'username' => '请输入用户名',
            'nickname' => '请输入昵称',
            'email' => '请输入邮箱',
            'phone' => '请输入手机号',
            'current_password' => '请输入当前密码',
            'new_password' => '请输入新密码（至少6位）',
            'confirm_password' => '请再次输入新密码',
        ],
        'tag' => [
            'enabled' => '启用',
            'disabled' => '禁用',
        ],
        'confirm' => [
            'delete' => '确定要删除用户 :name 吗？',
        ],
        'message' => [
            'not_found' => '用户不存在',
            'password_reset' => '密码重置成功',
            'status_updated' => '状态更新成功',
        ],
    ],

    // ==================== 角色管理 ====================
    'role' => [
        'title' => '角色管理',
        'column' => [
            'name' => '角色标识',
            'title' => '角色名称',
            'description' => '描述',
            'status' => '状态',
            'is_system' => '系统角色',
            'permissions' => '权限',
            'users_count' => '用户数',
            'created_at' => '创建时间',
            'actions' => '操作',
        ],
        'button' => [
            'create' => '新增',
            'edit' => '编辑',
            'delete' => '删除',
        ],
        'form' => [
            'name' => '角色标识',
            'title' => '角色名称',
            'description' => '描述',
            'permissions' => '权限',
            'status' => '状态',
        ],
        'tag' => [
            'enabled' => '启用',
            'disabled' => '禁用',
        ],
        'confirm' => [
            'delete' => '确定要删除该角色吗？',
        ],
        'message' => [
            'created' => '创建成功',
            'updated' => '更新成功',
            'deleted' => '删除成功',
            'permissions_updated' => '权限更新成功',
            'not_found' => '角色不存在',
        ],
    ],

    // ==================== 权限管理 ====================
    'permission' => [
        'title' => '权限管理',
        'column' => [
            'name' => '权限标识',
            'title' => '权限名称',
            'module' => '所属模块',
            'sort' => '排序',
            'actions' => '操作',
        ],
        'button' => [
            'create' => '新增',
            'edit' => '编辑',
            'delete' => '删除',
            'add_child' => '添加子权限',
        ],
        'form' => [
            'parent_id' => '父级权限',
            'name' => '权限标识',
            'title' => '权限名称',
            'module' => '所属模块',
            'description' => '描述',
            'sort' => '排序',
        ],
        'confirm' => [
            'delete' => '确定要删除该权限吗？',
        ],
        'message' => [
            'created' => '创建成功',
            'updated' => '更新成功',
            'deleted' => '删除成功',
            'not_found' => '权限不存在',
        ],
    ],

    // ==================== 菜单管理 ====================
    'menu' => [
        'title' => '菜单管理',
        'column' => [
            'name' => '路由名称',
            'title' => '菜单标题',
            'path' => '路由路径',
            'icon' => '图标',
            'order' => '排序',
            'hide_in_menu' => '隐藏',
            'actions' => '操作',
        ],
        'button' => [
            'create' => '新增',
            'edit' => '编辑',
            'delete' => '删除',
            'add_child' => '添加子菜单',
        ],
        'form' => [
            'parent_id' => '父级菜单',
            'name' => '路由名称',
            'title' => '菜单标题',
            'path' => '路由路径',
            'icon' => '图标',
            'redirect' => '重定向',
            'order' => '排序',
            'layout_type' => '布局类型',
            'open_type' => '打开方式',
            'href' => '外部链接',
            'use_json_renderer' => 'JSON渲染',
            'schema_source' => 'Schema来源',
            'hide_in_menu' => '隐藏',
            'keep_alive' => '保持连接',
            'requires_auth' => '需要认证',
            'is_default_after_login' => '登录后默认页',
        ],
        'tag' => [
            'hidden_yes' => '是',
            'hidden_no' => '否',
        ],
        'confirm' => [
            'delete' => '确定要删除该菜单吗？',
        ],
        'message' => [
            'created' => '创建成功',
            'updated' => '更新成功',
            'deleted' => '删除成功',
            'sorted' => '排序成功',
            'not_found' => '菜单不存在',
        ],
    ],

    // ==================== 数据字典 ====================
    'dict' => [
        'title' => '字典管理',
        'column' => [
            'code' => '字典编码',
            'name' => '字典名称',
            'items_count' => '字典项数',
            'label' => '显示名',
            'value' => '值',
            'sort' => '排序',
            'is_enabled' => '启用',
            'is_system' => '系统内置',
            'actions' => '操作',
        ],
        'button' => [
            'create_group' => '新增分组',
            'create_item' => '新增字典项',
            'edit' => '编辑',
            'delete' => '删除',
            'dict_items' => '字典项',
        ],
        'form' => [
            'code' => '字典编码',
            'name' => '字典名称',
            'description' => '描述',
            'label' => '显示名',
            'value' => '值',
            'sort' => '排序',
            'is_enabled' => '启用',
        ],
        'tag' => [
            'enabled' => '启用',
            'disabled' => '禁用',
        ],
        'title' => [
            'edit_item' => '编辑字典项',
            'new_item' => '新增字典项',
            'edit_group' => '编辑字典分组',
            'create_group' => '新增字典分组',
        ],
        'confirm' => [
            'delete_group' => '确定要删除该字典分组吗？',
            'delete_item' => '确定要删除该字典项吗？',
        ],
        'message' => [
            'created' => '创建成功',
            'updated' => '更新成功',
            'deleted' => '删除成功',
            'order_updated' => '排序更新成功',
            'load_failed' => '加载字典项失败',
        ],
    ],

    // ==================== CRUD ====================
    'crud' => [
        'message' => [
            'created' => '创建成功',
            'updated' => '更新成功',
            'deleted' => '删除成功',
            'status_updated' => '状态更新成功',
            'batch_deleted' => '批量删除成功',
            'sorted' => '排序成功',
            'order_updated' => '排序更新成功',
            'operation_failed' => '操作失败',
            'delete_failed' => '删除失败',
            'password_reset_ok' => '密码重置成功',
            'password_reset_failed' => '密码重置失败',
            'load_failed' => '加载失败',
        ],
    ],

    // ==================== 通知 ====================
    'notification' => [
        'button' => [
            'mark_read' => '标记为已读',
            'mark_all_read' => '全部标记为已读',
            'batch_delete' => '批量删除',
        ],
        'message' => [
            'sent' => '通知已发送',
            'marked_read' => '标记为已读',
            'all_marked_read' => '全部标记为已读',
        ],
    ],
];

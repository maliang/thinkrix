<?php

return [
    'admin' => [
        'notification' => [
            'main_backend' => '主后台',
        ],
    ],
    'crud' => [
        'resource_name' => '记录',
    ],
    'dict' => [
        'page_title' => '字典管理',
        'placeholder' => [
            'code' => '请输入字典编码',
            'name' => '请输入字典名称',
            'description' => '请输入描述',
            'item_code' => '请输入字典项编码',
            'item_label' => '请输入显示名',
            'item_value' => '请输入值',
            'sort' => '请输入排序',
        ],
        'search' => [
            'keyword' => '关键词',
            'placeholder' => '搜索编码或名称',
        ],
    ],
    'menu' => [
        'route' => [
            'home' => '首页',
            'system' => [
                '_' => '系统管理',
                'user' => '成员管理',
                'role' => '角色管理',
                'permission' => '权限管理',
                'menu' => '菜单管理',
                'dict' => '字典管理',
                'setting' => '系统设置',
            ],
            'module' => [
                '_' => '模块管理',
                'installed' => '已安装模块',
                'market' => '模块商城',
            ],
            'notification' => [
                '_' => '通知管理',
                'category' => '通知分类',
            ],
        ],
        'message' => [
            'cannot_parent_self' => '菜单不能选择自身或子菜单作为父级',
            'delete_children_first' => '请先删除子菜单',
            'parent_not_found' => '父级菜单不存在',
        ],
        'option' => [
            'layout_normal' => '普通布局',
            'layout_blank' => '空白布局',
            'open_normal' => '普通打开',
            'open_iframe' => 'iframe 打开',
            'open_new_window' => '新窗口打开',
        ],
        'placeholder' => [
            'parent' => '请选择父级菜单',
            'name' => '请输入路由名称',
            'title' => '请输入菜单标题',
            'path' => '请输入路由路径',
            'icon' => '请输入图标',
            'redirect' => '请输入重定向路径',
            'href' => '请输入外部链接',
            'schema_source' => '请输入 Schema 接口路径',
        ],
    ],
    'module' => [
        'config' => [
            'not_found' => '模块配置不存在',
        ],
        'logo' => [
            'not_configured' => '模块未配置 Logo',
            'file_not_found' => '模块 Logo 文件不存在',
        ],
    ],
    'notification' => [
        'resource_name' => '通知',
        'category' => [
            'resource' => '通知分类',
        ],
        'message' => [
            'broadcast_delete' => '广播通知不能在此删除',
            'broadcast_readonly' => '广播通知为只读',
        ],
    ],
    'permission' => [
        'message' => [
            'cannot_parent_self' => '权限不能选择自身或子权限作为父级',
            'delete_children_first' => '请先删除子权限',
            'parent_not_found' => '父级权限不存在',
            'updated' => '权限已更新',
        ],
        'placeholder' => [
            'parent' => '请选择父级权限',
            'name' => '请输入权限标识',
            'title' => '请输入权限名称',
            'module' => '请输入所属模块',
            'desc' => '请输入描述',
        ],
    ],
    'role' => [
        'message' => [
            'cannot_delete_system' => '系统角色不能删除',
        ],
        'placeholder' => [
            'name' => '请输入角色标识',
            'title' => '请输入角色名称',
            'description' => '请输入描述',
        ],
        'search' => [
            'placeholder' => '搜索角色标识或名称',
        ],
    ],
    'system' => [
        'avatar' => [
            'profile' => '个人资料',
            'settings' => '账号设置',
            'password' => '修改密码',
            'logout' => '退出登录',
        ],
        'entry' => [
            'assets_not_publish' => '后台前端资源尚未发布',
        ],
    ],
    'user' => [
        'column' => [
            'last_login_time' => '最后登录时间',
        ],
        'form' => [
            'remark' => '备注',
        ],
        'placeholder' => [
            'roles' => '请选择角色',
            'remark' => '请输入备注',
        ],
        'search' => [
            'placeholder' => '搜索用户名、昵称、邮箱或手机号',
        ],
        'filter' => [
            'all' => '全部',
        ],
        'export_prefix' => '用户',
    ],
];

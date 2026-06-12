<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 路由配置
    |--------------------------------------------------------------------------
    */
    'path' => env('THINKRIX_PATH', '/admin'),
    'api_prefix' => env('THINKRIX_API_PREFIX', 'api/admin'),
    'guard' => env('THINKRIX_GUARD', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | 系统信息
    |--------------------------------------------------------------------------
    */
    'app_title' => env('THINKRIX_APP_TITLE', 'Thinkrix Admin'),
    'logo' => env('THINKRIX_LOGO', '/admin/favicon.svg'),
    'copyright' => env('THINKRIX_COPYRIGHT', '© ' . date('Y') . ' Thinkrix Admin. All rights reserved.'),

    /*
    |--------------------------------------------------------------------------
    | 模型映射
    | 用户可继承默认模型并在此配置自定义模型类
    |--------------------------------------------------------------------------
    */
    'models' => [
        'user' => \Thinkrix\Models\AdminUser::class,
        'role' => \Thinkrix\Models\Role::class,
        'permission' => \Thinkrix\Models\Permission::class,
        'menu' => \Thinkrix\Models\Menu::class,
        'setting' => \Thinkrix\Models\Setting::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 控制器映射
    | 用户可继承默认控制器并在此配置自定义控制器类
    |--------------------------------------------------------------------------
    */
    'controllers' => [
        'auth' => \Thinkrix\Controllers\AuthController::class,
        'user' => \Thinkrix\Controllers\UserController::class,
        'role' => \Thinkrix\Controllers\RoleController::class,
        'permission' => \Thinkrix\Controllers\PermissionController::class,
        'menu' => \Thinkrix\Controllers\MenuController::class,
        'setting' => \Thinkrix\Controllers\SettingController::class,
        'system' => \Thinkrix\Controllers\SystemController::class,
        'home' => \Thinkrix\Controllers\HomeController::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据表映射
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'users' => 'admin_users',
        'menus' => 'admin_menus',
        'settings' => 'admin_settings',
        'roles' => 'roles',
        'permissions' => 'permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | 超级管理员角色
    | 拥有此角色的用户将拥有所有权限
    |--------------------------------------------------------------------------
    */
    'super_admin_role' => env('THINKRIX_SUPER_ADMIN_ROLE', 'super-admin'),

    /*
    |--------------------------------------------------------------------------
    | Token 认证配置
    |--------------------------------------------------------------------------
    */
    'token' => [
        'table' => 'personal_access_tokens',
        'prefix' => env('THINKRIX_TOKEN_PREFIX', 'thinkrix'),
        'expiration' => env('THINKRIX_TOKEN_EXPIRATION', 86400 * 7), // 7天
        'revoke_previous_tokens' => env('THINKRIX_REVOKE_PREVIOUS_TOKENS', false),
    ],

    'auth' => [
        'require_guard_role' => env('THINKRIX_REQUIRE_GUARD_ROLE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 缓存配置
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'menu' => [
            'enabled' => env('THINKRIX_MENU_CACHE_ENABLED', true),
            'key' => 'thinkrix.menus',
            'ttl' => 3600,
        ],
        'settings' => [
            'enabled' => env('THINKRIX_SETTINGS_CACHE_ENABLED', true),
            'prefix' => 'thinkrix.setting.',
            'ttl' => 3600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 默认头像
    |--------------------------------------------------------------------------
    */
    'default_avatar' => env('THINKRIX_DEFAULT_AVATAR', null),

    /*
    |--------------------------------------------------------------------------
    | 导航栏组件显示配置
    | 控制导航栏右侧各功能按钮的显示/隐藏
    |--------------------------------------------------------------------------
    */
    'header' => [
        'global_search' => env('THINKRIX_HEADER_GLOBAL_SEARCH', true),
        'notification' => env('THINKRIX_HEADER_NOTIFICATION', true),
        'full_screen' => env('THINKRIX_HEADER_FULL_SCREEN', true),
        'lang_switch' => env('THINKRIX_HEADER_LANG_SWITCH', true),
        'theme_schema_switch' => env('THINKRIX_HEADER_THEME_SCHEMA_SWITCH', true),
        'theme_button' => env('THINKRIX_HEADER_THEME_BUTTON', true),

        /*
        |----------------------------------------------------------------------
        | 自定义导航项
        | 用户可在宿主项目的 config/thinkrix.php 中覆盖此项，
        | 在导航栏右侧添加自定义功能入口。
        |
        | 简单图标按钮（无需自定义组件）：
        |   {
        |       'icon'       => 'carbon:rocket',
        |       'tooltip'    => '消息中心',
        |       'badge_api'  => '/api/custom/unread',   // 返回 { count: N }
        |       'badge_color'=> '#f00',
        |       'click'      => 'link',                 // link | modal | drawer
        |       'click_target'=> 'https://...',
        |   }
        |
        | 高级自定义（通过 schema_api 返回任意 schema UI）：
        |   {
        |       'schema_api' => '/api/admin/header/my-dropdown',
        |   }
        |   schema_api 接口应返回一个 schema 节点数组，支持任何 vschema-ui 组件
        |   （下拉菜单、开关、Popover 自定义内容、布局等），无需创建 Vue 组件。
        |----------------------------------------------------------------------
        */
        'custom_items' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | 模块市场配置
    | 连接远程模块市场获取可用模块
    |--------------------------------------------------------------------------
    */
    'module_market' => [
        'enabled' => env('THINKRIX_MODULE_MARKET_ENABLED', true),
        'api_url' => env('THINKRIX_MODULE_MARKET_URL', 'https://market.lartrix.com/api/market'),
        'timeout' => env('THINKRIX_MODULE_MARKET_TIMEOUT', 30),
        'cache_ttl' => env('THINKRIX_MODULE_MARKET_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知配置
    |--------------------------------------------------------------------------
    */
    'notification' => [
        'category_model' => \Thinkrix\Models\NotificationCategory::class,
        'message_model' => \Thinkrix\Models\NotificationMessage::class,
        'guard_user_models' => [
            'admin' => \Thinkrix\Models\AdminUser::class,
        ],
        'default_categories' => [
            [
                'key' => 'all',
                'name' => '全部',
                'icon' => 'ph:bell',
                'color' => '',
                'message_types' => [],
                'sort' => 0,
            ],
            [
                'key' => 'system',
                'name' => '系统',
                'icon' => 'ph:gear',
                'color' => '',
                'message_types' => ['system'],
                'sort' => 1,
            ],
            [
                'key' => 'notice',
                'name' => '通知',
                'icon' => 'ph:chat',
                'color' => '',
                'message_types' => ['notice'],
                'sort' => 2,
            ],
            [
                'key' => 'message',
                'name' => '消息',
                'icon' => 'ph:chat-circle',
                'color' => '',
                'message_types' => ['message'],
                'sort' => 3,
            ],
            [
                'key' => 'todo',
                'name' => '待办',
                'icon' => 'ph:check-square',
                'color' => '',
                'message_types' => ['todo'],
                'sort' => 4,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 实时消息配置
    | 控制通知中心的实时刷新方式，可选择轮询(polling)或 WebSocket(ws)
    | 开发者可继承 RealtimeService 实现自定义实时消息逻辑
    |--------------------------------------------------------------------------
    */
    'realtime' => [
        'enabled' => env('THINKRIX_REALTIME_ENABLED', true),
        'driver' => env('THINKRIX_REALTIME_DRIVER', 'polling'), // polling 或 ws
        'polling' => [
            'interval' => env('THINKRIX_REALTIME_POLLING_INTERVAL', 15000), // 毫秒
            'api' => '/notifications/poll',
        ],
        'websocket' => [
            'url' => env('THINKRIX_REALTIME_WS_URL', ''),
            'protocol' => env('THINKRIX_REALTIME_WS_PROTOCOL', 'ws'), // ws 或 wss
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 默认主题配置
    | 当数据库中未保存主题配置时使用此默认值
    |--------------------------------------------------------------------------
    */
    'theme' => [
        'appTitle' => env('THINKRIX_APP_TITLE', 'Thinkrix Admin'),
        'logo' => env('THINKRIX_LOGO', '/favicon.svg'),
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
            'breadcrumb' => ['visible' => true, 'showIcon' => true],
            'multilingual' => ['visible' => true],
            'globalSearch' => ['visible' => true],
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
            'text' => env('THINKRIX_APP_TITLE', 'Thinkrix Admin'),
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
    ],
];

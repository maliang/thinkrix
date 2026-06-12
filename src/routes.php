<?php

use think\facade\Route;

$prefix = config('thinkrix.api_prefix', 'api/admin');
$path = config('thinkrix.path', '/admin');
$guard = config('thinkrix.guard', 'admin');

// 从配置获取控制器类
$authController = config('thinkrix.controllers.auth', \Thinkrix\Controllers\AuthController::class);
$userController = config('thinkrix.controllers.user', \Thinkrix\Controllers\UserController::class);
$roleController = config('thinkrix.controllers.role', \Thinkrix\Controllers\RoleController::class);
$permissionController = config('thinkrix.controllers.permission', \Thinkrix\Controllers\PermissionController::class);
$menuController = config('thinkrix.controllers.menu', \Thinkrix\Controllers\MenuController::class);
$moduleController = config('thinkrix.controllers.module', \Thinkrix\Controllers\ModuleController::class);
$settingController = config('thinkrix.controllers.setting', \Thinkrix\Controllers\SettingController::class);
$systemController = config('thinkrix.controllers.system', \Thinkrix\Controllers\SystemController::class);
$homeController = config('thinkrix.controllers.home', \Thinkrix\Controllers\HomeController::class);
$dictController = config('thinkrix.controllers.dict', \Thinkrix\Controllers\DictController::class);
$notificationCategoryController = \Thinkrix\Controllers\NotificationCategoryController::class;
$notificationController = \Thinkrix\Controllers\NotificationController::class;
$adminNotificationController = \Thinkrix\Controllers\AdminNotificationController::class;

// 前端入口路由（处理 SPA 路由）
Route::get($path . '/<any?>', "{$systemController}@entry")->pattern(['any' => '.*']);

Route::group($prefix, function () use (
    $authController,
    $userController,
    $roleController,
    $permissionController,
    $menuController,
    $moduleController,
    $settingController,
    $systemController,
    $homeController,
    $dictController,
    $notificationCategoryController,
    $notificationController,
    $adminNotificationController
) {
    // 公开路由（无需认证）
    Route::post('auth/login', "{$authController}@login");
    Route::get('auth/config', "{$authController}@config");
    Route::get('login/page', "{$systemController}@loginPage");
    Route::get('system/theme-config', "{$systemController}@getThemeConfig");
    Route::get('modules/<name>/logo', "{$moduleController}@logo")->pattern(['name' => '[a-zA-Z0-9_-]+']);

    // 需要认证的路由
    Route::group(function () use (
        $authController,
        $userController,
        $roleController,
        $permissionController,
        $menuController,
        $moduleController,
        $settingController,
        $systemController,
        $homeController,
        $dictController,
        $notificationCategoryController,
        $notificationController,
        $adminNotificationController
    ) {
        // 认证相关
        Route::group('auth', function () use ($authController) {
            Route::post('logout', "{$authController}@logout");
            Route::post('refresh', "{$authController}@refresh");
            Route::get('user', "{$authController}@user");
            Route::get('tokens', "{$authController}@tokens");
            Route::delete('tokens/<id>', "{$authController}@revokeToken");
        });

        // 系统配置
        Route::group('system', function () use ($systemController) {
            Route::post('theme-config', "{$systemController}@saveThemeConfig")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.update');
        });

        // 布局相关
        Route::group('layout', function () use ($systemController) {
            Route::get('header-right', "{$systemController}@headerRight");
        });

        // 首页仪表盘
        Route::get('dashboard', "{$homeController}@dashboard");

        // 用户管理
        Route::get('users/<id>', "{$userController}@show")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.user.list');
        Route::put('users/<id>', "{$userController}@update")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, '*=system.user.update', 'status=system.user.status', 'reset_password=system.user.password');
        Route::get('users', "{$userController}@index")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.user.list');
        Route::post('users', "{$userController}@store")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.user.create');

        // 用户自助服务（个人中心、账号设置、修改密码）
        Route::get('user/profile/ui', "{$userController}@profileUi");
        Route::get('user/settings/ui', "{$userController}@settingsUi");
        Route::post('user/settings', "{$userController}@updateSettings");
        Route::get('user/password/ui', "{$userController}@passwordUi");
        Route::post('user/password', "{$userController}@updatePassword");
        Route::delete('users/<id>', "{$userController}@destroy")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.user.delete');

        // 角色管理
        Route::get('roles', "{$roleController}@index")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.role.list');
        Route::post('roles', "{$roleController}@store")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.role.create');
        Route::get('roles/<id>', "{$roleController}@show")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.role.list');
        Route::put('roles/<id>', "{$roleController}@update")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, '*=system.role.update', 'permissions=system.role.permissions');
        Route::delete('roles/<id>', "{$roleController}@destroy")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.role.delete');

        // 权限管理
        Route::get('permissions', "{$permissionController}@index")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.permission.list');
        Route::post('permissions', "{$permissionController}@store")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.permission.create');
        Route::get('permissions/<id>', "{$permissionController}@show")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.permission.list');
        Route::put('permissions/<id>', "{$permissionController}@update")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.permission.update');
        Route::delete('permissions/<id>', "{$permissionController}@destroy")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.permission.delete');

        // 菜单管理
        Route::get('menus', "{$menuController}@index")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, '*=', 'all=system.menu.list', 'list_ui=system.menu.list', 'form_ui=system.menu.list');
        Route::post('menus', "{$menuController}@store")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.menu.create');
        Route::get('menus/<id>', "{$menuController}@show")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.menu.list');
        Route::put('menus/<id>', "{$menuController}@update")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, '*=system.menu.update', 'sort=system.menu.sort');
        Route::delete('menus/<id>', "{$menuController}@destroy")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.menu.delete');

        // 模块管理
        Route::group('modules', function () use ($moduleController) {
            Route::get('/', "{$moduleController}@index")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, '*=module.installed.list', 'market=module.market.list', 'market_ui=module.market.list');
            Route::put('<name>/enable', "{$moduleController}@enable")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'module.installed.enable');
            Route::put('<name>/disable', "{$moduleController}@disable")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'module.installed.disable');
        });

        // 设置管理
        Route::group('settings', function () use ($settingController) {
            Route::get('/', "{$settingController}@index")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.list');
            Route::get('<group>', "{$settingController}@group")->pattern(['group' => '[a-zA-Z_]+'])
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.list');
            Route::put('/', "{$settingController}@update")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.update');
        });

        // 字典管理
        Route::group('dicts', function () use ($dictController) {
            Route::get('options/<code>', "{$dictController}@options");
            Route::post('options/batch', "{$dictController}@batchOptions");
            Route::get('groups', "{$dictController}@groups")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.list');
            Route::post('groups', "{$dictController}@createGroup")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.create');
            Route::get('groups/<id>', "{$dictController}@showGroup")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.list');
            Route::put('groups/<id>', "{$dictController}@updateGroup")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.update');
            Route::delete('groups/<id>', "{$dictController}@deleteGroup")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.delete');
            Route::get('groups/<groupId>/items', "{$dictController}@items")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.list');
            Route::post('groups/<groupId>/items', "{$dictController}@createItem")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.create');
            Route::get('groups/<groupId>/items/<id>', "{$dictController}@showItem")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.list');
            Route::put('groups/<groupId>/items/<id>', "{$dictController}@updateItem")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.update');
            Route::delete('groups/<groupId>/items/<id>', "{$dictController}@deleteItem")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.delete');
            Route::post('groups/<groupId>/items/sort', "{$dictController}@sortItems")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.dict.update');
        });

        // 通知分类管理
        Route::get('notification-categories', "{$notificationCategoryController}@index");
        Route::post('notification-categories', "{$notificationCategoryController}@store")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.update');
        Route::get('notification-categories/<id>', "{$notificationCategoryController}@show");
        Route::put('notification-categories/<id>', "{$notificationCategoryController}@update")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.update');
        Route::delete('notification-categories/<id>', "{$notificationCategoryController}@destroy")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.update');

        // 通知消息管理
        Route::get('notifications', "{$notificationController}@index");
        Route::post('notifications', "{$notificationController}@store")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.update');
        Route::get('notifications/<id>', "{$notificationController}@show");
        Route::put('notifications/<id>', "{$notificationController}@update")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.update');
        Route::delete('notifications/<id>', "{$notificationController}@destroy")
            ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.update');

        Route::post('notifications/<id>/mark-read', "{$notificationController}@markAsRead");
        Route::post('notifications/mark-all-read', "{$notificationController}@markAllAsRead");
        Route::get('notifications/poll', "{$notificationController}@poll");

        Route::group('admin', function () use ($adminNotificationController) {
            Route::post('notifications/send-to-backend', "{$adminNotificationController}@sendToBackend")
                ->middleware(\Thinkrix\Middleware\CheckPermission::class, 'system.setting.update');
            Route::get('notifications/sent', "{$adminNotificationController}@sentNotifications");
            Route::get('notifications/available-guards', "{$adminNotificationController}@availableGuards");
            Route::get('notifications/categories', "{$adminNotificationController}@categories");
        });

        // 获取当前用户的通知分类配置
        Route::get('notification/tabs', function () {
            $guard = config('thinkrix.guard', 'admin');
            $categories = \Thinkrix\Models\NotificationCategory::where('guard_name', $guard)
                ->where('enabled', true)
                ->order('sort')
                ->select();

            $tabs = [];
            foreach ($categories as $c) {
                $tabs[] = [
                    'key' => $c->key,
                    'label' => $c->name,
                    'icon' => $c->icon,
                    'color' => $c->color,
                    'types' => $c->message_types ?? [],
                ];
            }

            // 添加"全部"选项
            $allTab = ['key' => 'all', 'label' => '全部', 'icon' => 'ph:bell', 'color' => null, 'types' => []];
            array_unshift($tabs, $allTab);

            return success($tabs);
        });
    })->middleware(\Thinkrix\Middleware\Authenticate::class);
})->middleware(\Thinkrix\Middleware\HandleApiException::class);

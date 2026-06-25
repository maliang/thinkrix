<?php

return [
    'admin' => [
        'notification' => [
            'main_backend' => 'Main Backend',
        ],
    ],
    'crud' => [
        'resource_name' => 'Record',
    ],
    'dict' => [
        'page_title' => 'Dictionary',
        'placeholder' => [
            'code' => 'Enter dictionary code',
            'name' => 'Enter dictionary name',
            'description' => 'Enter description',
            'item_code' => 'Enter item code',
            'item_label' => 'Enter item label',
            'item_value' => 'Enter item value',
            'sort' => 'Enter sort order',
        ],
        'search' => [
            'keyword' => 'Keyword',
            'placeholder' => 'Search code or name',
        ],
    ],
    'menu' => [
        'route' => [
            'home' => 'Home',
            'system' => [
                '_' => 'System',
                'user' => 'Users',
                'role' => 'Roles',
                'permission' => 'Permissions',
                'menu' => 'Menus',
                'dict' => 'Dictionaries',
                'setting' => 'Settings',
            ],
            'module' => [
                '_' => 'Modules',
                'installed' => 'Installed Modules',
                'market' => 'Module Market',
            ],
            'notification' => [
                '_' => 'Notifications',
                'category' => 'Notification Categories',
            ],
        ],
        'message' => [
            'cannot_parent_self' => 'A menu cannot use itself or its child as parent',
            'delete_children_first' => 'Please delete child menus first',
            'parent_not_found' => 'Parent menu not found',
        ],
        'option' => [
            'layout_normal' => 'Normal Layout',
            'layout_blank' => 'Blank Layout',
            'open_normal' => 'Normal',
            'open_iframe' => 'Iframe',
            'open_new_window' => 'New Window',
        ],
        'placeholder' => [
            'parent' => 'Select parent menu',
            'name' => 'Enter route name',
            'title' => 'Enter menu title',
            'path' => 'Enter route path',
            'icon' => 'Enter icon',
            'redirect' => 'Enter redirect path',
            'href' => 'Enter external URL',
            'schema_source' => 'Enter schema API path',
        ],
    ],
    'module' => [
        'config' => [
            'not_found' => 'Module configuration not found',
        ],
        'logo' => [
            'not_configured' => 'Module logo is not configured',
            'file_not_found' => 'Module logo file not found',
        ],
    ],
    'notification' => [
        'resource_name' => 'Notification',
        'category' => [
            'resource' => 'Notification Category',
        ],
        'message' => [
            'broadcast_delete' => 'Broadcast notifications cannot be deleted here',
            'broadcast_readonly' => 'Broadcast notifications are read-only',
        ],
    ],
    'permission' => [
        'message' => [
            'cannot_parent_self' => 'A permission cannot use itself or its child as parent',
            'delete_children_first' => 'Please delete child permissions first',
            'parent_not_found' => 'Parent permission not found',
            'updated' => 'Permissions updated',
        ],
        'placeholder' => [
            'parent' => 'Select parent permission',
            'name' => 'Enter permission name',
            'title' => 'Enter display name',
            'module' => 'Enter module name',
            'desc' => 'Enter description',
        ],
    ],
    'role' => [
        'message' => [
            'cannot_delete_system' => 'System roles cannot be deleted',
        ],
        'placeholder' => [
            'name' => 'Enter role name',
            'title' => 'Enter display name',
            'description' => 'Enter description',
        ],
        'search' => [
            'placeholder' => 'Search role name or display name',
        ],
    ],
    'system' => [
        'avatar' => [
            'profile' => 'Profile',
            'settings' => 'Settings',
            'password' => 'Change Password',
            'logout' => 'Logout',
        ],
        'entry' => [
            'assets_not_publish' => 'Admin assets have not been published',
        ],
    ],
    'user' => [
        'column' => [
            'last_login_time' => 'Last Login',
        ],
        'form' => [
            'remark' => 'Remark',
        ],
        'placeholder' => [
            'roles' => 'Select roles',
            'remark' => 'Enter remark',
        ],
        'search' => [
            'placeholder' => 'Search username, nickname, email, or phone',
        ],
        'filter' => [
            'all' => 'All',
        ],
        'export_prefix' => 'Users',
    ],
];

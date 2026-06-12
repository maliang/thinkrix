<?php

namespace Thinkrix\Models;

use think\Model;
use think\model\relation\HasMany;
use think\model\relation\BelongsTo;

/**
 * Menu - 菜单模型
 *
 * @property int $id
 * @property string $guard_name
 * @property int|null $parent_id
 * @property string $name
 * @property string $path
 * @property string|null $component
 * @property string|null $redirect
 * @property string|null $title
 * @property string|null $icon
 * @property int $order
 * @property bool $hide_in_menu
 * @property bool $keep_alive
 * @property array|null $permissions
 * @property bool $use_json_renderer
 * @property string|null $schema_source
 * @property string|null $layout_type
 * @property string|null $open_type
 * @property string|null $href
 * @property bool $is_default_after_login
 * @property int|null $fixed_index_in_tab
 * @property bool $requires_auth
 * @property string|null $active_menu
 * @property string $created_at
 * @property string $updated_at
 */
class Menu extends Model
{
    protected $table = 'admin_menus';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'hide_in_menu' => 'boolean',
        'keep_alive' => 'boolean',
        'use_json_renderer' => 'boolean',
        'is_default_after_login' => 'boolean',
        'fixed_index_in_tab' => 'integer',
        'requires_auth' => 'boolean',
        'permissions' => 'array',
        'order' => 'integer',
    ];

    protected $fillable = [
        'guard_name', 'parent_id', 'name', 'path', 'component', 'redirect',
        'title', 'icon', 'order', 'hide_in_menu', 'keep_alive', 'permissions',
        'use_json_renderer', 'schema_source', 'layout_type', 'open_type', 'href',
        'is_default_after_login', 'fixed_index_in_tab', 'requires_auth', 'active_menu',
    ];

    /**
     * 按 guard 过滤菜单
     */
    public function scopeForGuard($query, ?string $guard = null)
    {
        $guard = $guard ?? config('thinkrix.guard', 'admin');
        return $query->where('guard_name', $guard);
    }

    /**
     * 父级菜单
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * 子级菜单
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->order('order');
    }

    /**
     * 递归获取所有子级菜单
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /**
     * 转换为 MenuRoute 格式
     */
    public function toMenuRoute(?array $userPermissions = null): array
    {
        $route = [
            'name' => $this->getData('name'),
            'path' => $this->path,
        ];

        if ($this->component) { $route['component'] = $this->component; }
        if ($this->redirect) { $route['redirect'] = $this->redirect; }

        $meta = [];
        if ($this->title) $meta['title'] = $this->title;
        if ($this->icon) $meta['icon'] = $this->icon;
        if ($this->order) $meta['order'] = $this->order;
        if ($this->hide_in_menu) $meta['hideInMenu'] = true;
        if ($this->keep_alive) $meta['keepAlive'] = true;
        if ($this->permissions) $meta['permissions'] = $this->permissions;
        if ($this->use_json_renderer) $meta['useJsonRenderer'] = true;
        if ($this->schema_source) $meta['schemaSource'] = $this->schema_source;
        if ($this->layout_type) $meta['layoutType'] = $this->layout_type;
        if ($this->open_type) $meta['openType'] = $this->open_type;
        if ($this->href) $meta['href'] = $this->href;
        if ($this->is_default_after_login) $meta['isDefaultAfterLogin'] = true;
        if ($this->fixed_index_in_tab !== null) $meta['fixedIndexInTab'] = $this->fixed_index_in_tab;
        if ($this->requires_auth) $meta['requiresAuth'] = true;
        if ($this->active_menu) $meta['activeMenu'] = $this->active_menu;

        if (!empty($meta)) { $route['meta'] = $meta; }

        $children = $this->allChildren;
        if ($children && !$children->isEmpty()) {
            if ($userPermissions !== null) {
                $children = $children->filter(fn($child) => $child->canAccess($userPermissions));
            }
            if (!$children->isEmpty()) {
                $route['children'] = $children->map(fn($child) => $child->toMenuRoute($userPermissions))->values()->toArray();
            }
        }

        return $route;
    }

    /**
     * 获取用户可访问的菜单树
     */
    public static function getRoutesForUser($user, ?string $guard = null): array
    {
        $userPermissions = array_map(function ($p) {
            return $p['name'] ?? '';
        }, $user->getActivePermissions()->toArray());
        $guard = $guard ?? config('thinkrix.guard', 'admin');

        $menus = static::whereNull('parent_id')
            ->forGuard($guard)
            ->with('allChildren')
            ->order('order')
            ->select();

        $result = [];
        foreach ($menus as $menu) {
            if ($menu->canAccess($userPermissions)) {
                $result[] = $menu->toMenuRoute($userPermissions);
            }
        }
        return $result;
    }

    /**
     * 检查用户是否有权限访问此菜单
     */
    public function canAccess(array $userPermissions): bool
    {
        if (!empty($this->permissions)) {
            return !empty(array_intersect($this->permissions, $userPermissions));
        }

        if (in_array($this->getData('name'), $userPermissions)) {
            return true;
        }

        $menuNamePrefix = $this->getData('name') . '.';
        foreach ($userPermissions as $permission) {
            if (str_starts_with($permission, $menuNamePrefix)) {
                return true;
            }
        }

        $permissionModel = config('thinkrix.models.permission', \Thinkrix\Models\Permission::class);
        $relatedPermissionExists = $permissionModel::where('name', $this->getData('name'))
            ->whereOr('name', 'like', $this->getData('name') . '.%')
            ->find();

        if ($relatedPermissionExists) {
            return false;
        }

        return true;
    }
}

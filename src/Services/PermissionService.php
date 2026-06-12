<?php

namespace Thinkrix\Services;

use Thinkrix\Models\Permission;
use Thinkrix\Models\Role;
use Thinkrix\Models\AdminUser;
use think\Model;

/**
 * PermissionService - 权限服务
 */
class PermissionService extends BaseService
{
    /**
     * 获取权限树（按模块分组）
     */
    public function getTreeByModule(): array
    {
        return Permission::getTreeByModule();
    }

    /**
     * 获取完整权限树
     */
    public function getTree(): array
    {
        return Permission::getTree();
    }

    /**
     * 获取用户的有效权限（排除禁用角色的权限）
     */
    public function getUserActivePermissions(Model $user): array
    {
        return $user->getActivePermissionNames();
    }

    /**
     * 检查用户是否有指定权限
     */
    public function userHasPermission(Model $user, string $permission): bool
    {
        return $user->hasActivePermission($permission);
    }

    /**
     * 检查用户是否有任一指定权限
     */
    public function userHasAnyPermission(Model $user, array $permissions): bool
    {
        $activePermissions = $this->getUserActivePermissions($user);
        return !empty(array_intersect($permissions, $activePermissions));
    }

    /**
     * 检查用户是否有所有指定权限
     */
    public function userHasAllPermissions(Model $user, array $permissions): bool
    {
        $activePermissions = $this->getUserActivePermissions($user);
        return empty(array_diff($permissions, $activePermissions));
    }

    /**
     * 获取角色的权限列表
     */
    public function getRolePermissions(Role $role): array
    {
        return $role->permissions()->column('name');
    }

    /**
     * 同步角色权限
     */
    public function syncRolePermissions(Role $role, array $permissionNames): void
    {
        $permissionModel = config('thinkrix.models.permission', Permission::class);
        $permissionIds = $permissionModel::whereIn('name', $permissionNames)
            ->where('guard_name', $role->guard_name)
            ->column('id');

        // 先删除旧权限
        app('db')->name('role_has_permissions')
            ->where('role_id', $role->id)
            ->delete();

        // 插入新权限
        $data = [];
        foreach ($permissionIds as $pid) {
            $data[] = [
                'role_id' => $role->id,
                'permission_id' => $pid,
            ];
        }
        if (!empty($data)) {
            app('db')->name('role_has_permissions')->insertAll($data);
        }
    }
}

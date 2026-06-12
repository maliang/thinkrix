<?php

namespace Thinkrix\Models;

use think\Model;
use think\model\Collection;
use think\model\relation\BelongsToMany;

/**
 * AdminUser - 管理员用户模型
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string|null $nickname
 * @property string|null $avatar
 * @property string|null $email
 * @property string|null $phone
 * @property string $status
 * @property string|null $remark
 * @property string|null $last_login_ip
 * @property string|null $last_login_time
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class AdminUser extends Model
{
    // 使用软删除
    use \think\model\concern\SoftDelete;

    protected $table = 'admin_users';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = 'deleted_at';

    protected $type = [
        'last_login_time' => 'datetime',
        'status' => 'string',
    ];

    protected $hidden = ['password'];

    protected $fillable = [
        'username', 'password', 'nickname', 'avatar', 'email', 'phone',
        'status', 'remark', 'last_login_ip', 'last_login_time',
    ];

    // 自动密码哈希
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * 角色关联
     */
    public function roles(): BelongsToMany
    {
        $roleModel = config('thinkrix.models.role', Role::class);
        return $this->belongsToMany($roleModel, 'model_has_roles', 'role_id', 'model_id')
            ->wherePivot('model_type', static::class);
    }

    /**
     * 检查是否为超级管理员
     */
    public function isSuperAdmin(): bool
    {
        $superAdminRole = config('thinkrix.super_admin_role', 'super-admin');
        return $this->hasRole($superAdminRole);
    }

    /**
     * 检查是否拥有角色
     */
    public function hasRole(string $roleName): bool
    {
        $roles = $this->roles()->where('name', $roleName)->where('status', 1)->select();
        return !$roles->isEmpty();
    }

    /**
     * 获取角色名称列表
     */
    public function getRoleNames(): array
    {
        return $this->roles()->column('name');
    }

    /**
     * 获取用户的有效权限（排除禁用角色的权限）
     * 超级管理员拥有所有权限
     */
    public function getActivePermissions()
    {
        if ($this->isSuperAdmin()) {
            $permissionModel = config('thinkrix.models.permission', Permission::class);
            return $permissionModel::where('guard_name', config('thinkrix.guard', 'admin'))->select();
        }

        $roleIds = $this->roles()->where('status', 1)->column('role_id');
        if (empty($roleIds)) {
            return new Collection([]);
        }

        $permissionModel = config('thinkrix.models.permission', Permission::class);
        return $permissionModel::alias('p')
            ->join('role_has_permissions rhp', 'p.id = rhp.permission_id')
            ->whereIn('rhp.role_id', $roleIds)
            ->where('p.guard_name', config('thinkrix.guard', 'admin'))
            ->group('p.id')
            ->select();
    }

    /**
     * 获取有效权限名称列表
     */
    public function getActivePermissionNames(): array
    {
        $permissions = $this->getActivePermissions();
        return array_column($permissions->toArray(), 'name');
    }

    /**
     * 检查用户是否有指定权限（排除禁用角色）
     */
    public function hasActivePermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        $names = $this->getActivePermissionNames();
        return in_array($permission, $names);
    }

    /**
     * 检查用户状态是否启用
     */
    public function isActive(): bool
    {
        return $this->status == 1 || $this->status === '1' || $this->status === true;
    }
}

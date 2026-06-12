<?php

namespace Thinkrix\Models;

use think\Model;
use think\model\relation\BelongsToMany;

/**
 * Role - 角色模型
 *
 * @property int $id
 * @property string $name
 * @property string|null $title
 * @property string $guard_name
 * @property string|null $description
 * @property bool $status
 * @property bool $is_system
 * @property string $created_at
 * @property string $updated_at
 */
class Role extends Model
{
    protected $table = 'roles';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'status' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected $fillable = [
        'name', 'title', 'guard_name', 'description', 'status', 'is_system',
    ];

    /**
     * 权限关联
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions', 'permission_id', 'role_id');
    }

    /**
     * 查询启用的角色
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', true);
    }

    /**
     * 查询禁用的角色
     */
    public function scopeDisabled($query)
    {
        return $query->where('status', false);
    }

    /**
     * 查询系统内置角色
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * 检查是否为系统内置角色
     */
    public function isSystemRole(): bool
    {
        return $this->is_system === true;
    }

    /**
     * 检查角色是否启用
     */
    public function isEnabled(): bool
    {
        return $this->status === true;
    }
}

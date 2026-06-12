<?php

namespace Thinkrix\Models;

use think\Model;
use think\model\relation\HasMany;
use think\model\relation\BelongsTo;

/**
 * Permission - 权限模型
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $title
 * @property string $guard_name
 * @property string|null $module
 * @property string|null $description
 * @property int $sort
 * @property string $created_at
 * @property string $updated_at
 */
class Permission extends Model
{
    protected $table = 'permissions';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'sort' => 'integer',
    ];

    protected $fillable = [
        'parent_id', 'name', 'title', 'guard_name', 'module', 'description', 'sort',
    ];

    /**
     * 父级权限
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    /**
     * 子级权限
     */
    public function children(): HasMany
    {
        return $this->hasMany(Permission::class, 'parent_id')->order('sort');
    }

    /**
     * 递归获取所有子级权限
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /**
     * 按模块分组获取权限树
     */
    public static function getTreeByModule(?string $guard = null): array
    {
        $query = static::whereNull('parent_id');
        if ($guard !== null) {
            $query->where('guard_name', $guard);
        }
        $permissions = $query
            ->with(['children' => function ($query) {
                $query->order('sort');
            }])
            ->order('module')
            ->order('sort')
            ->select();

        $result = [];
        foreach ($permissions as $p) {
            $module = $p->module ?: 'default';
            $result[$module][] = $p->toArray();
        }
        return $result;
    }

    /**
     * 获取完整的权限树
     */
    public static function getTree(?string $guard = null): array
    {
        $query = static::whereNull('parent_id');
        if ($guard !== null) {
            $query->where('guard_name', $guard);
        }
        return $query
            ->with('allChildren')
            ->order('sort')
            ->select()
            ->toArray();
    }
}

<?php

namespace Thinkrix\Models;

use think\Model;
use think\model\relation\HasMany;

/**
 * DictGroup - 字典分组模型
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_system
 * @property string $created_at
 * @property string $updated_at
 */
class DictGroup extends Model
{
    protected $table = 'dict_groups';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'is_system' => 'boolean',
    ];

    protected $fillable = [
        'code', 'name', 'description', 'is_system',
    ];

    /**
     * 关联字典项
     */
    public function items(): HasMany
    {
        return $this->hasMany(DictItem::class, 'group_id')->order('sort');
    }

    /**
     * 根据 code 获取分组
     */
    public static function findByCode(string $code): ?static
    {
        return static::where('code', $code)->find();
    }
}

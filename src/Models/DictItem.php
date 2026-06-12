<?php

namespace Thinkrix\Models;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * DictItem - 字典项模型
 *
 * @property int $id
 * @property int $group_id
 * @property string $code
 * @property string $label
 * @property string $value
 * @property int $sort
 * @property bool $is_enabled
 * @property array|null $extra
 * @property string $created_at
 * @property string $updated_at
 */
class DictItem extends Model
{
    protected $table = 'dict_items';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'sort' => 'integer',
        'is_enabled' => 'boolean',
        'extra' => 'array',
    ];

    protected $fillable = [
        'group_id', 'code', 'label', 'value', 'sort', 'is_enabled', 'extra',
    ];

    /**
     * 关联分组
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(DictGroup::class, 'group_id');
    }

    /**
     * 只查询启用的项
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}

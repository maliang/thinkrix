<?php

namespace Thinkrix\Models;

use think\Model;

/**
 * NotificationCategory - 通知分类模型
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property string|null $icon
 * @property string|null $color
 * @property int $sort
 * @property array $message_types
 * @property string $guard_name
 * @property bool $enabled
 * @property string $created_at
 * @property string $updated_at
 */
class NotificationCategory extends Model
{
    protected $table = 'notification_categories';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'message_types' => 'array',
        'enabled' => 'boolean',
        'sort' => 'integer',
    ];

    protected $fillable = [
        'name', 'key', 'icon', 'color', 'sort', 'message_types', 'guard_name', 'enabled',
    ];
}

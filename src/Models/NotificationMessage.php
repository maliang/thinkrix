<?php

namespace Thinkrix\Models;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * NotificationMessage - 通知消息模型
 *
 * @property int $id
 * @property string $title
 * @property string|null $content
 * @property string $type
 * @property string $category_key
 * @property string $guard_name
 * @property int|null $user_id
 * @property int|null $from_user_id
 * @property array|null $target_guards
 * @property bool $is_read
 * @property string|null $read_at
 * @property array|null $extra
 * @property string $created_at
 * @property string $updated_at
 */
class NotificationMessage extends Model
{
    protected $table = 'notification_messages';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'extra' => 'array',
        'target_guards' => 'array',
    ];

    protected $fillable = [
        'title', 'content', 'type', 'category_key', 'guard_name',
        'user_id', 'from_user_id', 'from_guard', 'target_guards', 'is_read', 'read_at', 'extra',
    ];

    /**
     * 关联接收用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('thinkrix.models.user'), 'user_id');
    }

    /**
     * 关联发送用户
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(config('thinkrix.models.user'), 'from_user_id');
    }

    /**
     * 关联通知分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NotificationCategory::class, 'category_key', 'key');
    }
}

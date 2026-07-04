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

    public function toArray(): array
    {
        $array = parent::toArray();

        $array['createdAt'] = $array['created_at'] ?? null;
        $array['updatedAt'] = $array['updated_at'] ?? null;
        $array['readAt'] = $array['read_at'] ?? null;
        $array['fromUserId'] = $array['from_user_id'] ?? null;
        $array['targetGuards'] = $array['target_guards'] ?? null;
        $array['categoryKey'] = $array['category_key'] ?? null;
        $array['isRead'] = (bool) ($array['is_read'] ?? false);
        $array['userId'] = $array['user_id'] ?? null;
        $array['guardName'] = $array['guard_name'] ?? null;

        $category = $array['category'] ?? null;
        if (!$category) {
            $relation = $this->getRelation('category');
            if ($relation instanceof Model) {
                $category = $relation->toArray();
            } elseif (is_array($relation) && !empty($relation)) {
                $category = $relation;
            }
        }

        if (is_array($category) && !empty($category)) {
            $array['category'] = [
                'name' => $category['name'] ?? null,
                'color' => $category['color'] ?? null,
                'icon' => $category['icon'] ?? null,
            ];
            $array['categoryLabel'] = '[' . ($category['name'] ?? ($array['categoryKey'] ?? '')) . ']';
        }

        return $array;
    }

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

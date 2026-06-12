<?php

namespace Thinkrix\Services;

use Thinkrix\Models\NotificationMessage;

/**
 * RealtimeService - 实时消息服务
 *
 * 提供通知轮询/WS 的数据接口，开发者可继承此类重写方法
 * 实现自定义的实时消息逻辑（如接入第三方推送、自定义 WS 协议等）
 */
class RealtimeService
{
    /**
     * 获取当前用户的新消息（用于轮询）
     * 子类可重写此方法实现自定义新消息查询逻辑
     */
    public function getNewMessages(int $userId, string $guard, int $sinceId = 0, string $type = ''): array
    {
        $query = NotificationMessage::where('guard_name', $guard)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->whereOr('user_id', null);
            })
            ->where('id', '>', $sinceId);

        if ($type && $type !== 'all') {
            $query->where('category_key', $type);
        }

        return $query->order('id', 'desc')->limit(10)->select()->toArray();
    }

    /**
     * 获取未读消息数量
     * 子类可重写此方法实现自定义未读数计算逻辑
     */
    public function getUnreadCount(int $userId, string $guard): int
    {
        return NotificationMessage::where('guard_name', $guard)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->whereOr('user_id', null);
            })
            ->where('is_read', false)
            ->count();
    }

    /**
     * 组装轮询响应
     */
    public function buildPollResponse(int $userId, string $guard, int $sinceId = 0, string $type = ''): array
    {
        $messages = $this->getNewMessages($userId, $guard, $sinceId, $type);
        $unreadCount = $this->getUnreadCount($userId, $guard);

        return [
            'messages' => $messages,
            'unread_count' => $unreadCount,
            'has_new' => !empty($messages),
            'server_time' => date('Y-m-d H:i:s'),
        ];
    }
}

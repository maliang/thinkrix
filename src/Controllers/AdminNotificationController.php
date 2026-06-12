<?php

namespace Thinkrix\Controllers;

use think\Request;

class AdminNotificationController extends Controller
{
    /**
     * 向二级后台发送通知
     */
    public function sendToBackend(): array
    {
        $data = request()->post();
        $this->validate($data, [
            'title' => 'require|max:255',
            'content' => 'require',
            'type' => 'max:50',
            'category_key' => 'require|max:50',
            'target_guards' => 'require|array',
            'target_guards.*' => 'require|max:50',
        ]);

        $user = $this->getUser();
        $currentGuard = config('thinkrix.guard', 'admin');
        $guardUserModels = config('thinkrix.notification.guard_user_models', []);
        $messageModel = config('thinkrix.notification.message_model', \Thinkrix\Models\NotificationMessage::class);
        $targets = [];

        foreach (array_unique($data['target_guards']) as $guard) {
            $userModel = $guard === $currentGuard
                ? config('thinkrix.models.user', \Thinkrix\Models\AdminUser::class)
                : ($guardUserModels[$guard] ?? null);
            if (!$userModel || !class_exists($userModel)) {
                return error("后台 {$guard} 未配置用户模型");
            }
            $targets[$guard] = $userModel::column('id');
        }

        foreach ($targets as $guard => $userIds) {
            foreach ($userIds as $userId) {
                $messageModel::create([
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'type' => $data['type'] ?? 'system',
                    'category_key' => $data['category_key'],
                    'guard_name' => $guard,
                    'user_id' => $userId,
                    'from_user_id' => $user->id,
                    'from_guard' => $currentGuard,
                    'target_guards' => $data['target_guards'],
                    'is_read' => false,
                ]);
            }
        }

        return success('通知已发送');
    }

    /**
     * 获取发送的通知列表
     */
    public function sentNotifications(): array
    {
        $user = $this->getUser();
        $messageModel = config('thinkrix.notification.message_model', \Thinkrix\Models\NotificationMessage::class);
        $notifications = $messageModel::where('from_user_id', $user->id)
            ->where('from_guard', config('thinkrix.guard', 'admin'))
            ->order('created_at', 'desc')
            ->paginate($this->input('page_size', 15));

        return success([
            'list' => $notifications->items(),
            'total' => $notifications->total(),
        ]);
    }

    /**
     * 获取可用的 guard 列表
     */
    public function availableGuards(): array
    {
        $guards = app('db')->name('notification_categories')
            ->where('enabled', true)
            ->column('guard_name');

        $guards = array_unique($guards);
        $result = [];
        foreach ($guards as $guard) {
            $result[] = [
                'value' => $guard,
                'label' => $guard === 'admin' ? '主后台' : $guard,
            ];
        }

        return success($result);
    }

    /**
     * 获取通知分类列表
     */
    public function categories(): array
    {
        $guard = config('thinkrix.guard', 'admin');
        $categories = \Thinkrix\Models\NotificationCategory::where('guard_name', $guard)
            ->where('enabled', true)
            ->order('sort')
            ->select();

        $result = [];
        foreach ($categories as $c) {
            $result[] = [
                'key' => $c->key,
                'name' => $c->name,
                'icon' => $c->icon,
                'color' => $c->color,
                'types' => $c->message_types ?? [],
            ];
        }

        return success($result);
    }
}

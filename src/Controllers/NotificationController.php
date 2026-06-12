<?php

namespace Thinkrix\Controllers;

use Thinkrix\Services\RealtimeService;

class NotificationController extends CrudController
{
    protected function getModelClass(): string
    {
        return config('thinkrix.notification.message_model', \Thinkrix\Models\NotificationMessage::class);
    }

    protected function getResourceName(): string { return '通知消息'; }

    protected function getDefaultOrder(): array { return ['created_at', 'desc']; }

    protected function applyFilters($query): void
    {
        $this->applyOwnershipScope($query);

        if ($this->input('type') !== null) {
            $query->where('type', $this->input('type'));
        }

        if ($this->input('is_read') !== null && $this->input('is_read') !== '') {
            $query->where('is_read', (int) $this->input('is_read'));
        }
    }

    public function markAsRead(int $id): array
    {
        $model = $this->findOrFail($id);
        if ($model->user_id === null) {
            throw new \Thinkrix\Exceptions\ApiException('共享广播不支持单用户修改已读状态', 40022);
        }
        $model->is_read = true;
        $model->read_at = date('Y-m-d H:i:s');
        $model->save();
        return success('标记为已读');
    }

    public function markAllAsRead(): array
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::where('is_read', false)
            ->where('guard_name', config('thinkrix.guard', 'admin'))
            ->where('user_id', $this->getUser()->id);
        $query
            ->update(['is_read' => true, 'read_at' => date('Y-m-d H:i:s')]);
        return success('全部标记为已读');
    }

    protected function findOrFail(int $id)
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::where($this->getPrimaryKey(), $id);
        $this->applyOwnershipScope($query);
        $model = $query->find();

        if (!$model) {
            throw new \Thinkrix\Exceptions\ApiException("{$this->getResourceName()}不存在", 40004);
        }

        return $model;
    }

    protected function batchDestroy(): array
    {
        $data = request()->post();
        $this->validate($data, [
            'ids' => 'require|array|min:1',
            'ids.*' => 'integer',
        ]);

        $modelClass = $this->getModelClass();
        $query = $modelClass::whereIn($this->getPrimaryKey(), $data['ids']);
        $this->applyOwnershipScope($query);
        $models = $query->select();

        if ($models->isEmpty()) {
            return error("未找到要删除的{$this->getResourceName()}");
        }

        $deleted = 0;
        foreach ($models as $model) {
            $this->beforeDelete($model);
            $deleted += (int) $model->delete();
        }

        return success('批量删除成功', ['deleted' => $deleted]);
    }

    protected function prepareUpdateData(array $validated): array
    {
        unset($validated['guard_name'], $validated['user_id'], $validated['from_user_id'], $validated['from_guard']);
        return $validated;
    }

    protected function beforeDelete($model): void
    {
        if ($model->user_id === null) {
            throw new \Thinkrix\Exceptions\ApiException('共享广播不能由单个接收用户删除', 40022);
        }
    }

    protected function getStoreRules(): array
    {
        return [
            'title' => 'require|max:255',
            'content' => 'require',
            'type' => 'require|max:50',
            'category_key' => 'require|max:50',
            'extra' => 'array',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        return [
            'title' => 'max:255',
            'content' => 'max:65535',
            'type' => 'max:50',
            'category_key' => 'max:50',
            'extra' => 'array',
        ];
    }

    protected function prepareStoreData(array $validated): array
    {
        $validated['guard_name'] = config('thinkrix.guard', 'admin');
        $validated['from_user_id'] = $this->getUser()->id;
        $validated['from_guard'] = config('thinkrix.guard', 'admin');
        $validated['is_read'] = false;
        return $validated;
    }

    protected function applyOwnershipScope($query): void
    {
        $user = $this->getUser();
        $guard = config('thinkrix.guard', 'admin');

        $query->where('guard_name', $guard);
        $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->whereOr('user_id', null);
        });
    }

    /**
     * 消息轮询接口（供 HeaderNotification 实时刷新使用）
     * GET /notifications/poll?since_id=0&type=all
     *
     * 开发者可通过继承 RealtimeService 重写 getNewMessages / getUnreadCount 方法
     * 实现自定义的实时消息推送逻辑
     */
    public function poll(): array
    {
        $user = $this->getUser();
        $guard = config('thinkrix.guard', 'admin');
        $sinceId = (int) $this->input('since_id', 0);
        $type = $this->input('type', 'all');

        $realtime = app()->make(RealtimeService::class);
        $data = $realtime->buildPollResponse($user->id, $guard, $sinceId, $type);

        return success($data);
    }
}

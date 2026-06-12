<?php

namespace Thinkrix\Controllers;

use think\Request;
use Thinkrix\Models\NotificationCategory;

class NotificationCategoryController extends CrudController
{
    protected function getModelClass(): string
    {
        return config('thinkrix.notification.category_model', \Thinkrix\Models\NotificationCategory::class);
    }

    protected function getResourceName(): string { return '通知分类'; }

    protected function applyResourceScope($query): void
    {
        $query->where('guard_name', config('thinkrix.guard', 'admin'));
    }

    protected function applyFilters($query): void
    {
    }

    protected function prepareStoreData(array $validated): array
    {
        $validated['guard_name'] = config('thinkrix.guard', 'admin');
        return $validated;
    }

    protected function getStoreRules(): array
    {
        return [
            'name' => 'require|max:100',
            'key' => 'require|max:50',
            'icon' => 'max:100',
            'color' => 'max:50',
            'sort' => 'integer',
            'message_types' => 'array',
            'enabled' => 'boolean',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        return $this->getStoreRules();
    }
}

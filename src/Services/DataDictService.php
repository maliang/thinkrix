<?php

namespace Thinkrix\Services;

use think\facade\Cache;
use Thinkrix\Models\DictGroup;

/**
 * DataDictService - 数据字典服务
 */
class DataDictService
{
    protected string $cachePrefix = 'thinkrix.dict.';
    protected int $cacheLifetime = 3600;

    /**
     * 获取字典项列表
     */
    public function get(string $groupCode, bool $enabledOnly = true): array
    {
        $cacheKey = $this->cachePrefix . $groupCode . ($enabledOnly ? '.enabled' : '.all');

        return Cache::remember($cacheKey, function () use ($groupCode, $enabledOnly) {
            $group = DictGroup::findByCode($groupCode);
            if (!$group) { return []; }

            $query = $group->items();
            if ($enabledOnly) { $query->enabled(); }

            $items = $query->select();
            $result = [];
            foreach ($items as $item) {
                $result[] = [
                    'code' => $item->code,
                    'label' => $item->label,
                    'value' => $item->value,
                    'extra' => $item->extra,
                ];
            }
            return $result;
        }, $this->cacheLifetime);
    }

    /**
     * 获取单个字典项的 label
     */
    public function getLabel(string $groupCode, string $itemCode): ?string
    {
        $items = $this->get($groupCode);
        foreach ($items as $item) {
            if ($item['code'] === $itemCode || $item['value'] === $itemCode) {
                return $item['label'];
            }
        }
        return null;
    }

    /**
     * 获取字典选项（value => label 格式）
     */
    public function options(string $groupCode): array
    {
        $items = $this->get($groupCode);
        $options = [];
        foreach ($items as $item) {
            $options[$item['value']] = $item['label'];
        }
        return $options;
    }

    /**
     * 获取字典选项（用于前端 select 组件）
     */
    public function selectOptions(string $groupCode): array
    {
        $items = $this->get($groupCode);
        return array_map(fn($item) => [
            'label' => $item['label'],
            'value' => $item['value'],
        ], $items);
    }

    /**
     * 清除指定分组的缓存
     */
    public function clearCache(string $groupCode): void
    {
        Cache::delete($this->cachePrefix . $groupCode . '.enabled');
        Cache::delete($this->cachePrefix . $groupCode . '.all');
    }

    /**
     * 清除所有字典缓存
     */
    public function clearAllCache(): void
    {
        $groups = DictGroup::select();
        foreach ($groups as $group) {
            $this->clearCache($group->code);
        }
    }
}

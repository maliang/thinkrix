<?php

namespace Thinkrix\Support;

/** 统一维护模块市场分类选项、标签和查询归一化规则。 */
final class ModuleMarketTypes
{
    public function moduleOptions(): array
    {
        return [['label' => '全部', 'value' => 'all'], ['label' => '基础能力', 'value' => 'core'],
            ['label' => '业务模块', 'value' => 'business'], ['label' => '外部集成', 'value' => 'integration'],
            ['label' => '界面组件', 'value' => 'ui'], ['label' => '开发工具', 'value' => 'tooling']];
    }

    public function projectOptions(): array
    {
        return [['label' => '全部', 'value' => 'all'], ['label' => '起步模板', 'value' => 'starter'],
            ['label' => '行业方案', 'value' => 'solution'], ['label' => '演示项目', 'value' => 'demo'],
            ['label' => '结构模板', 'value' => 'template'], ['label' => '企业工程', 'value' => 'enterprise']];
    }

    public function label(string $type, string $kind): string
    {
        foreach ($kind === 'project' ? $this->projectOptions() : $this->moduleOptions() as $option) {
            if ($option['value'] === $type) { return $option['label']; }
        }
        return $type === '' ? '-' : $type;
    }

    public function normalize(mixed $type): string
    {
        $type = is_string($type) ? trim($type) : '';
        return $type === 'all' ? '' : $type;
    }
}

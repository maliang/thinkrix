<?php

namespace Thinkrix\Services;

/** 组合本地模块投影与发布状态，供模块管理列表使用。 */
final class ModuleManagementService
{
    public function __construct(private readonly ModuleService $modules, private readonly ModulePublishService $publishing)
    {
    }

    public function modules(): array
    {
        return $this->publishing->withPublishState($this->modules->getModules());
    }
}

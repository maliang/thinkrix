<?php

use think\migration\Migrator;

/** 为已有 Thinkrix 项目幂等补齐模块管理和市场权限。 */
class SyncModulePermissions extends Migrator
{
    /** 补齐新路由依赖的权限节点。 */
    public function up(): void
    {
        if (!$this->hasTable('permissions')) {
            return;
        }

        $module = $this->ensurePermission('module', '模块管理', null, 9980);
        $installed = $this->ensurePermission('module.installed', '已装模块', $module, 1);
        $market = $this->ensurePermission('module.market', '模块市场', $module, 2);
        $this->ensurePermission('module.installed.install', '安装模块', $installed, 4);
        $this->ensurePermission('module.installed.uninstall', '卸载模块', $installed, 5);
        $this->ensurePermission('module.market.publish', '发布模块和项目', $market, 3);
    }

    /** 权限可能已分配给角色，回滚版本时保留数据避免破坏授权。 */
    public function down(): void
    {
    }

    /** 返回已存在或新创建的权限 ID。 */
    private function ensurePermission(string $name, string $title, ?int $parentId, int $sort): int
    {
        $query = app()->db->name('permissions');
        $existing = $query->where('name', $name)->where('guard_name', 'admin')->find();
        if (is_array($existing)) {
            return (int) $existing['id'];
        }

        return (int) app()->db->name('permissions')->insertGetId([
            'parent_id' => $parentId,
            'name' => $name,
            'title' => $title,
            'guard_name' => 'admin',
            'module' => 'thinkrix',
            'sort' => $sort,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

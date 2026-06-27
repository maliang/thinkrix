<?php

use think\migration\Migrator;

/**
 * admin_menus 表添加 badge 字段
 * 用于保存菜单徽标配置，例如绑定通知类型的未读数量。
 */
class AddBadgeToAdminMenus extends Migrator
{
    public function change(): void
    {
        $table = $this->table('admin_menus');
        if (!$table->hasColumn('badge')) {
            $table->addColumn('badge', 'text', [
                'null' => true,
                'default' => null,
                'comment' => '菜单徽标配置（JSON）',
                'after' => 'active_menu',
            ])->update();
        }
    }
}

<?php

use think\migration\Migrator;

/**
 * admin_menus 表添加 module 字段
 * 用于按模块查询和删除菜单（配合模块安装/卸载）
 */
class AddModuleToAdminMenus extends Migrator
{
    public function change(): void
    {
        $table = $this->table('admin_menus');
        if (!$table->hasColumn('module')) {
            $table->addColumn('module', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => '所属模块',
                'after' => 'guard_name',
            ])->update();
        }
    }
}

<?php

use think\migration\Migrator;
use think\migration\db\Table;

class CreateNotificationCategoriesTable extends Migrator
{
    public function change()
    {
        if ($this->hasTable('notification_categories')) {
            return;
        }

        $table = $this->table('notification_categories', ['id' => true, 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        $table->addColumn('name', 'string', ['limit' => 100, 'comment' => '分类名称'])
            ->addColumn('key', 'string', ['limit' => 50, 'comment' => '分类标识'])
            ->addColumn('icon', 'string', ['limit' => 100, 'null' => true, 'comment' => '图标'])
            ->addColumn('color', 'string', ['limit' => 50, 'null' => true, 'comment' => '颜色'])
            ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])
            ->addColumn('message_types', 'text', ['null' => true, 'comment' => '消息类型（JSON数组）'])
            ->addColumn('guard_name', 'string', ['limit' => 50, 'default' => 'admin', 'comment' => '所属guard'])
            ->addColumn('enabled', 'boolean', ['default' => true, 'comment' => '是否启用'])
            ->addTimestamps('created_at', 'updated_at')
            ->addIndex(['key', 'guard_name'], ['unique' => true, 'name' => 'idx_key_guard'])
            ->create();
    }
}

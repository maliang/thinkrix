<?php

use think\migration\Migrator;
use think\migration\db\Table;

class CreateDictTables extends Migrator
{
    public function change()
    {
        // 字典分组表
        if (!$this->hasTable('dict_groups')) {
            $table = $this->table('dict_groups', ['id' => true, 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
            $table->addColumn('code', 'string', ['limit' => 50, 'comment' => '分组编码'])
                ->addColumn('name', 'string', ['limit' => 100, 'comment' => '分组名称'])
                ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'comment' => '描述'])
                ->addColumn('is_system', 'boolean', ['default' => false, 'comment' => '是否系统内置'])
                ->addTimestamps('created_at', 'updated_at')
                ->addIndex(['code'], ['unique' => true, 'name' => 'idx_code'])
                ->create();
        } else {
            $table = $this->table('dict_groups');
            if (!$table->hasColumn('created_at')) {
                $table->addColumn('created_at', 'datetime', ['null' => true]);
            }
            if (!$table->hasColumn('updated_at')) {
                $table->addColumn('updated_at', 'datetime', ['null' => true]);
            }
            $table->update();
        }

        // 字典项表
        if (!$this->hasTable('dict_items')) {
            $table = $this->table('dict_items', ['id' => true, 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
            $table->addColumn('group_id', 'integer', ['signed' => false, 'comment' => '分组ID'])
                ->addColumn('code', 'string', ['limit' => 50, 'comment' => '项编码'])
                ->addColumn('label', 'string', ['limit' => 100, 'comment' => '显示文本'])
                ->addColumn('value', 'string', ['limit' => 100, 'comment' => '存储值'])
                ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])
                ->addColumn('is_enabled', 'boolean', ['default' => true, 'comment' => '是否启用'])
                ->addColumn('extra', 'text', ['null' => true, 'comment' => '额外数据（JSON）'])
                ->addTimestamps('created_at', 'updated_at')
                ->addIndex(['group_id', 'code'], ['unique' => true, 'name' => 'idx_group_code'])
                ->addForeignKey('group_id', 'dict_groups', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        } else {
            $table = $this->table('dict_items');
            $table->changeColumn('group_id', 'integer', ['signed' => false, 'null' => true, 'comment' => '分组ID']);
            if (!$table->hasColumn('created_at')) {
                $table->addColumn('created_at', 'datetime', ['null' => true]);
            }
            if (!$table->hasColumn('updated_at')) {
                $table->addColumn('updated_at', 'datetime', ['null' => true]);
            }
            if (!$table->hasForeignKey('group_id')) {
                $table->addForeignKey('group_id', 'dict_groups', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
            }
            $table->update();
        }
    }
}

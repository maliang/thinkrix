<?php

use think\migration\Migrator;
use think\migration\db\Table;

class CreateNotificationMessagesTable extends Migrator
{
    public function change()
    {
        if ($this->hasTable('notification_messages')) {
            return;
        }

        $table = $this->table('notification_messages', ['id' => true, 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        $table->addColumn('title', 'string', ['limit' => 255, 'comment' => '消息标题'])
            ->addColumn('content', 'text', ['null' => true, 'comment' => '消息内容'])
            ->addColumn('type', 'string', ['limit' => 50, 'default' => 'system', 'comment' => '消息类型'])
            ->addColumn('category_key', 'string', ['limit' => 50, 'comment' => '分类标识'])
            ->addColumn('guard_name', 'string', ['limit' => 50, 'default' => 'admin', 'comment' => '所属guard'])
            ->addColumn('user_id', 'integer', ['null' => true, 'comment' => '接收用户ID（null为所有用户）'])
            ->addColumn('from_user_id', 'integer', ['null' => true, 'comment' => '发送用户ID'])
            ->addColumn('target_guards', 'text', ['null' => true, 'comment' => '目标guards（JSON数组）'])
            ->addColumn('is_read', 'boolean', ['default' => false, 'comment' => '是否已读'])
            ->addColumn('read_at', 'timestamp', ['null' => true, 'comment' => '阅读时间'])
            ->addColumn('extra', 'text', ['null' => true, 'comment' => '额外数据（JSON）'])
            ->addTimestamps('created_at', 'updated_at')
            ->addIndex(['user_id'], ['name' => 'idx_user_id'])
            ->addIndex(['is_read'], ['name' => 'idx_is_read'])
            ->addIndex(['created_at'], ['name' => 'idx_created_at'])
            ->create();
    }
}

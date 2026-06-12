<?php

use think\migration\Migrator;

class AddFromGuardToNotificationMessages extends Migrator
{
    public function up(): void
    {
        $table = $this->table('notification_messages');
        if ($table->hasColumn('from_guard')) {
            return;
        }

        $table
            ->addColumn('from_guard', 'string', ['limit' => 50, 'null' => true, 'after' => 'from_user_id'])
            ->addIndex(['from_guard', 'from_user_id'])
            ->update();
    }

    public function down(): void
    {
        $table = $this->table('notification_messages');
        if ($table->hasColumn('from_guard')) {
            $table->removeColumn('from_guard')->update();
        }
    }
}

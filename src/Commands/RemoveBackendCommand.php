<?php

namespace Thinkrix\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use Thinkrix\Services\ModuleService;
use Thinkrix\Commands\Module\Support\ModuleMigrationRollback;

class RemoveBackendCommand extends Command
{
    protected function configure()
    {
        $this->setName('thinkrix:remove-backend')
            ->setDescription('删除二级后台模块')
            ->addArgument('name', Argument::REQUIRED, '二级后台名称')
            ->addOption('force', 'f', Option::VALUE_NONE, '跳过确认')
            ->addOption('keep-data', null, Option::VALUE_NONE, '保留二级后台数据表和角色数据');
    }

    protected function execute(Input $input, Output $output)
    {
        $name = ucfirst($input->getArgument('name'));
        if (!preg_match('/^[A-Z][A-Za-z0-9_]*$/', $name)) {
            $output->error('模块名称只能包含字母、数字和下划线，且必须以字母开头。');
            return 1;
        }
        $moduleDir = $this->app->getRootPath() . 'app' . DIRECTORY_SEPARATOR . $name;

        if (!is_dir($moduleDir)) {
            $output->error("二级后台模块 {$name} 不存在。");
            return 1;
        }

        if (!$input->getOption('force') && !$output->confirm($input, "确认删除二级后台模块 {$name}？", false)) {
            $output->info('操作已取消。');
            return 0;
        }

        if (!$input->getOption('keep-data')) {
            $migrationPath = $moduleDir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
            if (is_dir($migrationPath)) {
                try {
                    $command = new ModuleMigrationRollback($migrationPath);
                    $command->setApp($this->app);
                    $command->setConsole($this->getConsole());
                    $command->run(new Input([$command->getName(), '--target=0', '--force']), $output);
                } catch (\Throwable $e) {
                    $output->error('数据库清理失败，模块目录未删除: ' . $e->getMessage());
                    return 1;
                }
            }
        }

        $this->rmDir($moduleDir);
        try {
            ModuleService::make()->syncModules();
        } catch (\Throwable $e) {
            $output->writeln('<comment>模块目录已删除，但数据库注册同步失败: ' . $e->getMessage() . '</comment>');
        }
        $output->info("二级后台模块 {$name} 已删除。");

        return 0;
    }

    protected function rmDir(string $dir): void
    {
        if (!is_dir($dir)) { return; }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) { rmdir($item); }
            else { unlink($item); }
        }
        rmdir($dir);
    }
}

<?php

namespace Thinkrix\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;

class UninstallCommand extends Command
{
    protected function configure()
    {
        $this->setName('thinkrix:uninstall')
            ->setDescription('卸载 Thinkrix 后台管理系统')
            ->addOption('force', 'f', Option::VALUE_NONE, '跳过确认');
    }

    protected function execute(Input $input, Output $output)
    {
        $rootPath = $this->app->getRootPath();
        $publicDir = $rootPath . 'public' . DIRECTORY_SEPARATOR . 'admin';

        if (!is_dir($publicDir)) {
            $output->writeln('<comment>未发现已发布的前端资源。</comment>');
        } else {
            // 确认删除
            if (!$input->getOption('force') && !$output->confirm($input, '确认删除 public/admin 目录？', false)) {
                $output->info('操作已取消。');
                return 0;
            }
            $this->rmDir($publicDir);
            $output->info('前端资源已删除。');
        }

        $output->info('Thinkrix 前端资源已清理。');
        $output->writeln('<comment>注意：数据库表和数据需要手动清理。</comment>');

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

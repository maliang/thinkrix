<?php

namespace Thinkrix\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class PublishAssetsCommand extends Command
{
    protected function configure()
    {
        $this->setName('thinkrix:publish')
            ->setDescription('发布前端资源到 public/admin');
    }

    protected function execute(Input $input, Output $output)
    {
        $sourceDir = __DIR__ . '/../../resources/admin';
        $rootPath = $this->app->getRootPath();
        $targetDir = $rootPath . 'public' . DIRECTORY_SEPARATOR . 'admin';

        if (!is_dir($sourceDir)) {
            $output->error('前端资源目录不存在: ' . $sourceDir);
            return 1;
        }

        if (is_dir($targetDir)) {
            $output->writeln('<comment>目标目录已存在，将覆盖文件...</comment>');
        }

        $this->copyDir($sourceDir, $targetDir);
        $output->info('前端资源发布完成。');

        return 0;
    }

    protected function copyDir(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($items as $item) {
            $target = $dest . DIRECTORY_SEPARATOR . $items->getSubPathname();
            if ($item->isDir()) {
                mkdir($target, 0755, true);
            } else {
                copy($item, $target);
            }
        }
    }
}

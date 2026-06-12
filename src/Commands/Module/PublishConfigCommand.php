<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

/**
 * 模块配置发布命令
 *
 * 将指定模块的 config/config.php 发布到项目的 config/modules/ 目录，
 * 使其可以在项目级别进行覆盖配置。
 *
 * 用法：
 *   php think thinkrix:module-publish-config Blog
 */
class PublishConfigCommand extends BaseModuleCommand
{
    protected function configure()
    {
        $this->setName('thinkrix:module-publish-config')
            ->setDescription('发布模块配置到项目目录')
            ->addArgument('module', Argument::REQUIRED, '目标模块名称');
    }

    protected function execute(Input $input, Output $output): int
    {
        $module = $input->getArgument('module');
        $generator = $this->getGenerator();
        $moduleName = $generator->studlyCase($module);

        // 验证模块存在
        if (!$this->validateModuleExists($moduleName, $output)) {
            return 1;
        }

        $modulePath = $generator->getModulePath($moduleName);
        $lowerName = strtolower($moduleName);

        // 源文件：模块的 config/config.php
        $sourceFile = $modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

        if (!file_exists($sourceFile)) {
            $output->writeln("<error>Module [{$moduleName}] has no config file (config/config.php).</error>");
            return 1;
        }

        // 目标路径：项目的 config/modules/{lower_name}.php
        $targetDir = app()->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'modules';
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $lowerName . '.php';

        // 确保目标目录存在
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 复制文件
        if (file_exists($targetFile)) {
            $output->writeln("<comment>Config file already exists: {$targetFile}</comment>");
            $output->writeln("<comment>Overwriting...</comment>");
        }

        copy($sourceFile, $targetFile);

        $output->writeln("<info>Config published: {$sourceFile} → {$targetFile}</info>");
        $output->writeln("<info>Access config via: config('module_{$lowerName}.key')</info>");

        return 0;
    }
}

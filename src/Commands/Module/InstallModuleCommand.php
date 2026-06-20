<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use Thinkrix\Services\ModuleService;

/**
 * 模块安装命令
 *
 * 通过 CLI 安装指定模块，自动完成迁移、填充、菜单/权限注册。
 * 模块未启用状态下即可执行此命令（不依赖模块自己的命令注册）。
 *
 * 用法：
 *   php think thinkrix:module-install UserCenter
 *   php think thinkrix:module-install Blog
 */
class InstallModuleCommand extends BaseModuleCommand
{
    protected function configure()
    {
        $this->setName('thinkrix:module-install')
            ->setDescription('安装模块（迁移+填充+注册菜单权限）')
            ->addArgument('name', Argument::REQUIRED, '模块名称');
    }

    protected function execute(Input $input, Output $output): int
    {
        $name = $input->getArgument('name');
        $moduleName = $this->getGenerator()->studlyCase($name);

        $modulePath = $this->getGenerator()->getModulePath($moduleName);
        if (!is_dir($modulePath)) {
            $output->writeln("<error>Module [{$moduleName}] directory not found.</error>");
            return 1;
        }
        if (!file_exists($modulePath . DIRECTORY_SEPARATOR . 'module.json')) {
            $output->writeln("<error>Module [{$moduleName}] module.json not found.</error>");
            return 1;
        }

        $output->info("正在安装模块: {$moduleName}...");

        $moduleService = new ModuleService();
        $result = $moduleService->install($moduleName);

        if (!$result) {
            $output->writeln("<error>Module [{$moduleName}] installation failed.</error>");
            return 1;
        }

        $output->writeln("<info>Module [{$moduleName}] installed successfully.</info>");
        return 0;
    }
}

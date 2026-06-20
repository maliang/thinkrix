<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use Thinkrix\Services\ModuleService;

/**
 * 模块卸载命令
 *
 * 通过 CLI 卸载指定模块，自动清理菜单/权限并回滚迁移。
 *
 * 用法：
 *   php think thinkrix:module-uninstall UserCenter
 *   php think thinkrix:module-uninstall Blog
 */
class UninstallModuleCommand extends BaseModuleCommand
{
    protected function configure()
    {
        $this->setName('thinkrix:module-uninstall')
            ->setDescription('卸载模块（清理菜单权限+回滚迁移）')
            ->addArgument('name', Argument::REQUIRED, '模块名称');
    }

    protected function execute(Input $input, Output $output): int
    {
        $name = $input->getArgument('name');
        $moduleName = $this->getGenerator()->studlyCase($name);

        $output->info("正在卸载模块: {$moduleName}...");

        $moduleService = new ModuleService();
        $result = $moduleService->uninstall($moduleName);

        if (!$result) {
            $output->writeln("<error>Module [{$moduleName}] uninstallation failed.</error>");
            return 1;
        }

        $output->writeln("<info>Module [{$moduleName}] uninstalled successfully.</info>");
        return 0;
    }
}

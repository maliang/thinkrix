<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use Thinkrix\Services\ModuleService;
use Thinkrix\Models\Module;

/**
 * 模块卸载命令
 *
 * 通过 CLI 卸载指定模块，自动清理菜单/权限并回滚迁移。
 *
 * 用法：
 *   php think thinkrix:module-uninstall             卸载所有已安装的模块
 *   php think thinkrix:module-uninstall Blog        卸载单个
 *   php think thinkrix:module-uninstall Blog Shop   一次卸载多个
 */
class UninstallModuleCommand extends BaseModuleCommand
{
    protected function configure()
    {
        $this->setName('thinkrix:module-uninstall')
            ->setDescription('卸载模块（不传参数则卸载所有已安装的模块）')
            ->addArgument('name', Argument::OPTIONAL | Argument::IS_ARRAY, '模块名称（可多个，不传则卸载全部）');
    }

    protected function execute(Input $input, Output $output): int
    {
        $names = $input->getArgument('name');
        $moduleService = new ModuleService();

        if (empty($names)) {
            $allModules = Module::where('enabled', true)->select();
            foreach ($allModules as $m) {
                $this->uninstallSingle($m->name, $moduleService, $output);
            }
            return 0;
        }

        foreach ($names as $name) {
            $moduleName = $this->getGenerator()->studlyCase($name);
            $this->uninstallSingle($moduleName, $moduleService, $output);
        }

        return 0;
    }

    protected function uninstallSingle(string $moduleName, ModuleService $moduleService, Output $output): void
    {
        $output->info("正在卸载模块: {$moduleName}...");
        $result = $moduleService->uninstall($moduleName);

        if ($result) {
            $output->writeln("<info>Module [{$moduleName}] uninstalled successfully.</info>");
        } else {
            $output->writeln("<error>Module [{$moduleName}] uninstallation failed.</error>");
        }
    }
}

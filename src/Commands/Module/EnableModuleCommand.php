<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use Thinkrix\Services\ModuleService;

/**
 * 模块启用命令
 *
 * 通过 CLI 启用指定模块，更新数据库中的模块状态为启用。
 *
 * 用法：
 *   php think thinkrix:module-enable UserCenter
 *   php think thinkrix:module-enable user-center
 */
class EnableModuleCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述和参数
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-enable')
            ->setDescription('启用指定模块')
            ->addArgument('name', Argument::REQUIRED, '模块名称（支持 StudlyCase、snake_case、kebab-case）');
    }

    /**
     * 执行命令逻辑
     *
     * @param Input $input 输入实例
     * @param Output $output 输出实例
     * @return int 退出码（0=成功，1=失败）
     */
    protected function execute(Input $input, Output $output): int
    {
        $name = $input->getArgument('name');

        // 将输入名称转换为 StudlyCase
        $moduleName = $this->getGenerator()->studlyCase($name);

        // 调用 ModuleService 启用模块
        $moduleService = new ModuleService();
        $result = $moduleService->enable($moduleName);

        if (!$result) {
            $output->writeln("<error>Module [{$moduleName}] not found.</error>");
            return 1;
        }

        $output->writeln("<info>Module [{$moduleName}] enabled successfully.</info>");

        return 0;
    }
}

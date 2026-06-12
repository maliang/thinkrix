<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use Thinkrix\Services\ModuleService;

/**
 * 模块禁用命令
 *
 * 通过 CLI 禁用指定模块，更新数据库中的模块状态为禁用。
 *
 * 用法：
 *   php think thinkrix:module-disable UserCenter
 *   php think thinkrix:module-disable user-center
 */
class DisableModuleCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述和参数
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-disable')
            ->setDescription('禁用指定模块')
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

        // 调用 ModuleService 禁用模块
        $moduleService = new ModuleService();
        $result = $moduleService->disable($moduleName);

        if (!$result) {
            $output->writeln("<error>Module [{$moduleName}] not found.</error>");
            return 1;
        }

        $output->writeln("<info>Module [{$moduleName}] disabled successfully.</info>");

        return 0;
    }
}

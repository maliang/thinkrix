<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

/**
 * 事件生成命令
 *
 * 在指定模块中生成事件文件，自动设置命名空间为 app\{ModuleName}\event。
 *
 * 用法：
 *   php think thinkrix:module-make-event UserCreated Blog
 */
class MakeEventCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述、参数
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-make-event')
            ->setDescription('在指定模块中生成事件')
            ->addArgument('name', Argument::REQUIRED, '事件名称（资源名）')
            ->addArgument('module', Argument::REQUIRED, '目标模块名称');
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
        $module = $input->getArgument('module');
        $generator = $this->getGenerator();

        // 将模块名转换为 StudlyCase
        $moduleName = $generator->studlyCase($module);

        // 验证目标模块存在
        if (!$this->validateModuleExists($moduleName, $output)) {
            return 1;
        }

        // 在模块的 event/ 目录下生成事件文件
        $filePath = $generator->generateResource($moduleName, 'event', $name);

        if (empty($filePath)) {
            $output->writeln("<error>Event [{$name}] creation failed in module [{$moduleName}].</error>");
            return 1;
        }

        $output->writeln("<info>Event [{$name}] created successfully in module [{$moduleName}].</info>");

        return 0;
    }
}

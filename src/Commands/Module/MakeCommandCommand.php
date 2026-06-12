<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

/**
 * 命令生成命令
 *
 * 在指定模块中生成命令文件，自动设置命名空间为 app\{ModuleName}\command。
 * 生成的命令名称格式为 {module_lower}:{command_snake_case}。
 *
 * 用法：
 *   php think thinkrix:module-make-command sync-data Blog
 */
class MakeCommandCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述、参数
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-make-command')
            ->setDescription('在指定模块中生成命令')
            ->addArgument('name', Argument::REQUIRED, '命令名称（资源名）')
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

        // 在模块的 command/ 目录下生成命令文件
        // command.stub 模板会自动使用 {{LOWER_NAME}}:{{TABLE_NAME}} 格式设置命令名称
        // LOWER_NAME = 模块名小写，TABLE_NAME = 命令名的 snake_case
        $filePath = $generator->generateResource($moduleName, 'command', $name);

        if (empty($filePath)) {
            $output->writeln("<error>Command [{$name}] creation failed in module [{$moduleName}].</error>");
            return 1;
        }

        $output->writeln("<info>Command [{$name}] created successfully in module [{$moduleName}].</info>");

        return 0;
    }
}

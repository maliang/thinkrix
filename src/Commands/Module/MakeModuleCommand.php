<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use Thinkrix\Services\ModuleService;

/**
 * 模块创建命令
 *
 * 通过 CLI 快速创建标准模块骨架目录结构。
 * 支持标准模式（完整目录结构 + 示例文件）和 --plain 模式（仅目录结构）。
 *
 * 用法：
 *   php think thinkrix:module-make UserCenter
 *   php think thinkrix:module-make user-center --plain
 *   php think thinkrix:module-make blog --title="博客系统"
 */
class MakeModuleCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述、参数和选项
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-make')
            ->setDescription('创建新的模块骨架')
            ->addArgument('name', Argument::REQUIRED, '模块名称（支持 StudlyCase、snake_case、kebab-case）')
            ->addOption('plain', null, Option::VALUE_NONE, '仅生成最小目录结构，不包含示例文件')
            ->addOption('title', null, Option::VALUE_OPTIONAL, '模块显示标题');
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
        $generator = $this->getGenerator();

        // 将输入名称转换为 StudlyCase
        $moduleName = $generator->studlyCase($name);

        // 检查同名模块是否已存在
        if ($generator->moduleExists($moduleName)) {
            $output->writeln("<error>Module [{$moduleName}] already exists.</error>");
            return 1;
        }

        // 构建选项
        $isPlain = $input->hasOption('plain') && $input->getOption('plain');
        $title = $input->getOption('title') ?: $moduleName;

        $options = [
            'plain' => $isPlain,
            'title' => $title,
        ];

        // 调用生成器创建模块
        $result = $generator->createModule($moduleName, $options);

        if (!$result) {
            $output->writeln("<error>Module [{$moduleName}] creation failed.</error>");
            return 1;
        }

        // 同步数据库，将新模块注册为启用状态
        $moduleService = ModuleService::make();
        $moduleService->syncModules();

        $output->writeln("<info>Module [{$moduleName}] created successfully.</info>");

        return 0;
    }
}

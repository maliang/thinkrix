<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

/**
 * Seeder 生成命令
 *
 * 在指定模块中生成 Seeder 文件，自动设置命名空间为 app\{ModuleName}\database。
 *
 * 用法：
 *   php think thinkrix:module-make-seeder UserSeeder Blog
 */
class MakeSeederCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述、参数
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-make-seeder')
            ->setDescription('在指定模块中生成 Seeder')
            ->addArgument('name', Argument::REQUIRED, 'Seeder 名称（资源名）')
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

        // 在模块的 database/seeders/ 目录下生成 Seeder 文件
        $filePath = $generator->generateResource($moduleName, 'seeder', $name);

        if (empty($filePath)) {
            $output->writeln("<error>Seeder [{$name}] creation failed in module [{$moduleName}].</error>");
            return 1;
        }

        $output->writeln("<info>Seeder [{$name}] created successfully in module [{$moduleName}].</info>");

        return 0;
    }
}

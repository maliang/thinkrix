<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

/**
 * 迁移文件生成命令
 *
 * 在指定模块中生成数据库迁移文件，文件名包含时间戳前缀。
 *
 * 用法：
 *   php think thinkrix:module-make-migration create_users Blog
 */
class MakeMigrationCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述、参数
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-make-migration')
            ->setDescription('在指定模块中生成迁移文件')
            ->addArgument('name', Argument::REQUIRED, '迁移名称（资源名）')
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

        // 在模块的 database/migrations/ 目录下生成迁移文件
        $filePath = $generator->generateResource($moduleName, 'migration', $name);

        if (empty($filePath)) {
            $output->writeln("<error>Migration [{$name}] creation failed in module [{$moduleName}].</error>");
            return 1;
        }

        $output->writeln("<info>Migration [{$name}] created successfully in module [{$moduleName}].</info>");

        return 0;
    }
}

<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\Table;
use Thinkrix\Services\ModuleService;

/**
 * 模块列表命令
 *
 * 以表格形式输出所有已注册模块的名称、状态（启用/禁用）和版本信息。
 *
 * 用法：
 *   php think thinkrix:module-list
 */
class ListModuleCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称和描述
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-list')
            ->setDescription('列出所有已注册的模块');
    }

    /**
     * 执行命令逻辑
     *
     * @param Input $input 输入实例
     * @param Output $output 输出实例
     * @return int 退出码（0=成功）
     */
    protected function execute(Input $input, Output $output): int
    {
        $moduleService = new ModuleService();
        $modules = $moduleService->getModules();

        if (empty($modules)) {
            $output->writeln('<info>No modules found.</info>');
            return 0;
        }

        // 构建表格行数据
        $rows = [];
        foreach ($modules as $module) {
            $rows[] = [
                $module['name'] ?? '',
                ($module['enabled'] ?? false) ? '<info>Enabled</info>' : '<comment>Disabled</comment>',
                $module['version'] ?? '1.0.0',
            ];
        }

        // 使用 ThinkPHP Table 类以表格形式输出模块信息
        $table = new Table();
        $table->setHeader(['Name', 'Status', 'Version']);
        $table->setRows($rows);

        $this->table($table);

        return 0;
    }
}

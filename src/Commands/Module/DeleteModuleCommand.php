<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use Thinkrix\Services\ModuleService;

/**
 * 模块删除命令
 *
 * 通过 CLI 删除指定模块，包括模块目录和数据库注册记录。
 * 执行前需要用户交互式确认，避免误删操作。
 *
 * 用法：
 *   php think thinkrix:module-delete UserCenter
 *   php think thinkrix:module-delete user-center
 */
class DeleteModuleCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述和参数
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-delete')
            ->setDescription('删除指定模块（含目录和数据库记录）')
            ->addArgument('name', Argument::REQUIRED, '模块名称（支持 StudlyCase、snake_case、kebab-case）');
    }

    /**
     * 执行命令逻辑
     *
     * @param Input $input 输入实例
     * @param Output $output 输出实例
     * @return int 退出码（0=成功/取消，1=失败）
     */
    protected function execute(Input $input, Output $output): int
    {
        $name = $input->getArgument('name');

        // 将输入名称转换为 StudlyCase
        $moduleName = $this->getGenerator()->studlyCase($name);

        // 验证模块是否存在（文件系统）
        if (!$this->validateModuleExists($moduleName, $output)) {
            return 1;
        }

        // 交互式确认删除操作
        $answer = $output->ask($input, "确认删除模块 [{$moduleName}]? 输入 'yes' 确认");

        if ($answer !== 'yes') {
            $output->writeln("<info>Operation cancelled.</info>");
            return 0;
        }

        // 删除模块目录（递归）
        $modulePath = $this->getModulePath($moduleName);
        $this->removeDirectory($modulePath);

        // 通过 ModuleService 删除数据库注册记录
        $moduleService = new ModuleService();
        $moduleService->delete($moduleName);

        $output->writeln("<info>Module [{$moduleName}] deleted successfully.</info>");

        return 0;
    }

    /**
     * 递归删除目录及其所有内容
     *
     * @param string $path 目录路径
     * @return void
     */
    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
            } else {
                unlink($itemPath);
            }
        }

        rmdir($path);
    }
}

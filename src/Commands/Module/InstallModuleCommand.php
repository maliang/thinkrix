<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use Thinkrix\Services\ModuleService;
use Thinkrix\Models\Module;

/**
 * 模块安装命令
 *
 * 通过 CLI 安装指定模块，自动完成迁移、填充、菜单/权限注册。
 * 模块未启用状态下即可执行此命令（不依赖模块自己的命令注册）。
 *
 * 用法：
 *   php think thinkrix:module-install             安装所有未安装的模块
 *   php think thinkrix:module-install Blog        安装单个模块
 *   php think thinkrix:module-install Blog Shop   一次安装多个模块
 */
class InstallModuleCommand extends BaseModuleCommand
{
    protected function configure()
    {
        $this->setName('thinkrix:module-install')
            ->setDescription('安装模块（不传参数则安装所有未安装的模块）')
            ->addArgument('name', Argument::OPTIONAL | Argument::IS_ARRAY, '模块名称（可多个，不传则安装全部）');
    }

    protected function execute(Input $input, Output $output): int
    {
        $names = $input->getArgument('name');
        $moduleService = new ModuleService();

        // 不传参数则扫描所有模块目录
        if (empty($names)) {
            $paths = config('thinkrix.modules.paths', ['Modules', 'app']);
            $root = app()->getRootPath();
            foreach ($paths as $p) {
                $dir = $root . $p . DIRECTORY_SEPARATOR;
                if (!is_dir($dir)) { continue; }
                $items = scandir($dir);
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') { continue; }
                    $moduleDir = $dir . $item;
                    if (!is_dir($moduleDir)) { continue; }
                    if (!file_exists($moduleDir . DIRECTORY_SEPARATOR . 'module.json')) { continue; }
                    $this->installSingle($item, $moduleService, $output);
                }
            }
            return 0;
        }

        foreach ($names as $name) {
            $moduleName = $this->getGenerator()->studlyCase($name);
            $this->installSingle($moduleName, $moduleService, $output);
        }

        return 0;
    }

    protected function installSingle(string $moduleName, ModuleService $moduleService, Output $output): void
    {
        $modulePath = $this->getGenerator()->getModulePath($moduleName);
        if (!is_dir($modulePath)) {
            $output->writeln("<error>Module [{$moduleName}] directory not found.</error>");
            return;
        }
        if (!file_exists($modulePath . DIRECTORY_SEPARATOR . 'module.json')) {
            $output->writeln("<error>Module [{$moduleName}] module.json not found.</error>");
            return;
        }

        $output->info("正在安装模块: {$moduleName}...");
        $result = $moduleService->install($moduleName);

        if ($result) {
            $output->writeln("<info>Module [{$moduleName}] installed successfully.</info>");
        } else {
            $output->writeln("<error>Module [{$moduleName}] installation failed.</error>");
        }
    }
}

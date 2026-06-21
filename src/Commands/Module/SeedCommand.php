<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use Thinkrix\Commands\Module\Support\ModuleSeedRun;

/**
 * 模块数据填充命令
 *
 * 执行指定模块 database/seeders/ 目录下的所有 Seeder 文件。
 *
 * 用法：
 *   php think thinkrix:module-seed                  # 填充全部模块
 *   php think thinkrix:module-seed Blog             # 填充单个
 *   php think thinkrix:module-seed Blog Shop        # 一次填充多个
 */
class SeedCommand extends BaseModuleCommand
{
    protected function configure()
    {
        $this->setName('thinkrix:module-seed')
            ->setDescription('执行模块数据填充（不传参数则填充全部模块）')
            ->addArgument('module', Argument::OPTIONAL | Argument::IS_ARRAY, '模块名称（可多个，不传则填充全部）');
    }

    protected function execute(Input $input, Output $output): int
    {
        $modules = $input->getArgument('module');

        if (empty($modules)) {
            return $this->seedAllModules($output);
        }

        $hasError = false;
        foreach ($modules as $module) {
            $result = $this->seedModule($module, $output);
            if ($result !== 0) { $hasError = true; }
        }
        return $hasError ? 1 : 0;
    }

    protected function seedModule(string $module, Output $output): int
    {
        $generator = $this->getGenerator();
        $moduleName = $generator->studlyCase($module);

        if (!$this->validateModuleExists($moduleName, $output)) {
            return 1;
        }

        $modulePath = $generator->getModulePath($moduleName);
        $seederPath = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders';

        if (!is_dir($seederPath)) {
            $output->writeln("<comment>Module [{$moduleName}] has no seeders directory.</comment>");
            return 0;
        }

        $files = glob($seederPath . DIRECTORY_SEPARATOR . '*.php');
        sort($files);

        if (empty($files)) {
            $output->writeln("<comment>Module [{$moduleName}] has no seeder files.</comment>");
            return 0;
        }

        $output->writeln("<info>Seeding module [{$moduleName}]...</info>");
        try {
            $command = new ModuleSeedRun($seederPath);
            $command->setApp($this->app);
            $command->setConsole($this->getConsole());
            $command->run(new Input([$command->getName()]), $output);
        } catch (\Throwable $e) {
            $output->writeln("<error>Seeding failed for module [{$moduleName}]: {$e->getMessage()}</error>");
            return 1;
        }

        $output->writeln("<info>Seeding complete for module [{$moduleName}].</info>");
        return 0;
    }

    protected function seedAllModules(Output $output): int
    {
        $paths = config('thinkrix.modules.paths', ['Modules', 'app']);
        $root = app()->getRootPath();
        $hasError = false;

        foreach ($paths as $p) {
            $dir = $root . $p . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) { continue; }
            $items = scandir($dir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') { continue; }
                $moduleDir = $dir . $item;
                if (!is_dir($moduleDir)) { continue; }
                if (!file_exists($moduleDir . DIRECTORY_SEPARATOR . 'module.json')) { continue; }
                $result = $this->seedModule($item, $output);
                if ($result !== 0) { $hasError = true; }
            }
        }

        return $hasError ? 1 : 0;
    }
}

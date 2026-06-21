<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use Thinkrix\Commands\Module\Support\ModuleMigrationRun;
use Thinkrix\Commands\Module\Support\ModuleMigrationRollback;

/**
 * 模块迁移命令
 *
 * 执行指定模块或所有模块的数据库迁移文件。
 * 支持 rollback（回滚）和 refresh（重建）操作。
 *
 * 用法：
 *   php think thinkrix:module-migrate                      # 迁移全部模块
 *   php think thinkrix:module-migrate Blog                 # 迁移单个
 *   php think thinkrix:module-migrate Blog Shop            # 同时迁移多个
 *   php think thinkrix:module-migrate Blog --rollback      # 回滚
 *   php think thinkrix:module-migrate Blog --refresh       # 重建
 */
class MigrateCommand extends BaseModuleCommand
{
    protected function configure()
    {
        $this->setName('thinkrix:module-migrate')
            ->setDescription('执行模块数据库迁移（不传参数则迁移全部模块）')
            ->addArgument('module', Argument::OPTIONAL | Argument::IS_ARRAY, '模块名称（可多个，不传则迁移全部）')
            ->addOption('rollback', null, Option::VALUE_NONE, '回滚最近一批迁移')
            ->addOption('refresh', null, Option::VALUE_NONE, '回滚并重新执行所有迁移');
    }

    protected function execute(Input $input, Output $output): int
    {
        $modules = $input->getArgument('module');
        $isRollback = $input->getOption('rollback');
        $isRefresh = $input->getOption('refresh');

        if (empty($modules)) {
            return $this->migrateAllModules($isRollback, $isRefresh, $output);
        }

        $hasError = false;
        foreach ($modules as $module) {
            $result = $this->migrateModule($module, $isRollback, $isRefresh, $output);
            if ($result !== 0) { $hasError = true; }
        }
        return $hasError ? 1 : 0;
    }

    protected function migrateModule(string $module, bool $rollback, bool $refresh, Output $output): int
    {
        $generator = $this->getGenerator();
        $moduleName = $generator->studlyCase($module);

        if (!$generator->moduleExists($moduleName)) {
            $output->writeln("<error>Module [{$moduleName}] does not exist.</error>");
            return 1;
        }

        $modulePath = $generator->getModulePath($moduleName);
        $migrationPath = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';

        if (!is_dir($migrationPath)) {
            $output->writeln("<comment>Module [{$moduleName}] has no migrations directory.</comment>");
            return 0;
        }

        $files = glob($migrationPath . DIRECTORY_SEPARATOR . '*.php');
        sort($files);

        if (empty($files)) {
            $output->writeln("<comment>Module [{$moduleName}] has no migration files.</comment>");
            return 0;
        }

        try {
            if ($refresh) {
                $output->writeln("<info>Refreshing migrations for module [{$moduleName}]...</info>");
                $this->runMigrationCommand(new ModuleMigrationRollback($migrationPath), ['--target=0', '--force'], $output);
                $this->runMigrationCommand(new ModuleMigrationRun($migrationPath), [], $output);
            } elseif ($rollback) {
                $output->writeln("<info>Rolling back migrations for module [{$moduleName}]...</info>");
                $this->runMigrationCommand(new ModuleMigrationRollback($migrationPath), ['--force'], $output);
            } else {
                $output->writeln("<info>Running migrations for module [{$moduleName}]...</info>");
                $this->runMigrationCommand(new ModuleMigrationRun($migrationPath), [], $output);
            }
        } catch (\Throwable $e) {
            $output->writeln("<error>Migration failed for module [{$moduleName}]: {$e->getMessage()}</error>");
            return 2;
        }

        $output->writeln("<info>Migration complete for module [{$moduleName}].</info>");
        return 0;
    }

    protected function migrateAllModules(bool $rollback, bool $refresh, Output $output): int
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
                $result = $this->migrateModule($item, $rollback, $refresh, $output);
                if ($result !== 0) { $hasError = true; }
            }
        }

        return $hasError ? 1 : 0;
    }

    protected function runMigrationCommand(\think\console\Command $command, array $arguments, Output $output): void
    {
        $command->setApp($this->app);
        $command->setConsole($this->getConsole());
        $command->run(new Input(array_merge([$command->getName()], $arguments)), $output);
    }
}

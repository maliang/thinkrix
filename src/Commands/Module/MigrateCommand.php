<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use Thinkrix\Services\ModuleService;
use Thinkrix\Commands\Module\Support\ModuleMigrationRun;
use Thinkrix\Commands\Module\Support\ModuleMigrationRollback;

/**
 * 模块迁移命令
 *
 * 执行指定模块或所有已启用模块的数据库迁移文件。
 * 支持 rollback（回滚）和 refresh（重建）操作。
 *
 * 用法：
 *   php think thinkrix:module-migrate              # 执行所有已启用模块的迁移
 *   php think thinkrix:module-migrate Blog         # 仅执行 Blog 模块的迁移
 *   php think thinkrix:module-migrate Blog --rollback   # 回滚 Blog 模块最近一批迁移
 *   php think thinkrix:module-migrate Blog --refresh    # 重建 Blog 模块所有迁移
 */
class MigrateCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述、参数和选项
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-migrate')
            ->setDescription('执行模块数据库迁移')
            ->addArgument('module', Argument::OPTIONAL, '目标模块名称（不指定时执行所有已启用模块）')
            ->addOption('rollback', null, Option::VALUE_NONE, '回滚最近一批迁移')
            ->addOption('refresh', null, Option::VALUE_NONE, '回滚并重新执行所有迁移');
    }

    /**
     * 执行命令逻辑
     *
     * @param Input $input 输入实例
     * @param Output $output 输出实例
     * @return int 退出码（0=成功，1=模块错误，2=系统异常）
     */
    protected function execute(Input $input, Output $output): int
    {
        $module = $input->getArgument('module');
        $isRollback = $input->getOption('rollback');
        $isRefresh = $input->getOption('refresh');

        if ($module) {
            return $this->migrateModule($module, $isRollback, $isRefresh, $output);
        }

        return $this->migrateAllModules($isRollback, $isRefresh, $output);
    }

    /**
     * 执行指定模块的迁移
     *
     * @param string $module 模块名称（原始输入）
     * @param bool $rollback 是否回滚
     * @param bool $refresh 是否刷新（回滚+重新执行）
     * @param Output $output 输出实例
     * @return int 退出码
     */
    protected function migrateModule(string $module, bool $rollback, bool $refresh, Output $output): int
    {
        $generator = $this->getGenerator();
        $moduleName = $generator->studlyCase($module);

        // 验证模块目录存在
        if (!$generator->moduleExists($moduleName)) {
            $output->writeln("<error>Module [{$moduleName}] does not exist.</error>");
            return 1;
        }

        // 验证模块是否已启用
        $moduleService = new ModuleService();
        if (!$moduleService->isEnabled($moduleName)) {
            $output->writeln("<error>Module [{$moduleName}] is not enabled.</error>");
            return 1;
        }

        $modulePath = $generator->getModulePath($moduleName);
        $migrationPath = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';

        if (!is_dir($migrationPath)) {
            $output->writeln("<comment>Module [{$moduleName}] has no migrations directory.</comment>");
            return 0;
        }

        // 获取迁移文件（按文件名排序，时间戳前缀保证顺序）
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

    /**
     * 执行所有已启用模块的迁移
     *
     * @param bool $rollback 是否回滚
     * @param bool $refresh 是否刷新
     * @param Output $output 输出实例
     * @return int 退出码
     */
    protected function migrateAllModules(bool $rollback, bool $refresh, Output $output): int
    {
        $moduleService = new ModuleService();

        try {
            $modules = $moduleService->getModules();
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to query modules: " . $e->getMessage() . "</error>");
            return 2;
        }

        $enabledModules = array_filter($modules, fn($m) => !empty($m['enabled']));

        if (empty($enabledModules)) {
            $output->writeln("<comment>No enabled modules found.</comment>");
            return 0;
        }

        $hasError = false;

        foreach ($enabledModules as $module) {
            $moduleName = $module['name'];
            $result = $this->migrateModule($moduleName, $rollback, $refresh, $output);
            if ($result !== 0) {
                $hasError = true;
            }
        }

        return $hasError ? 1 : 0;
    }

    /**
     * 执行迁移文件列表
     *
     * 逐个加载迁移文件并执行指定方向（up/down）的方法。
     * 迁移文件应返回一个包含 up() 和 down() 方法的匿名类或对象。
     *
     * @param array $files 迁移文件路径列表
     * @param string $direction 执行方向（'up' 或 'down'）
     * @param Output $output 输出实例
     */
    protected function runMigrationCommand(\think\console\Command $command, array $arguments, Output $output): void
    {
        $command->setApp($this->app);
        $command->setConsole($this->getConsole());
        $command->run(new Input(array_merge([$command->getName()], $arguments)), $output);
    }
}

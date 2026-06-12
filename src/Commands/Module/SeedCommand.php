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
 *   php think thinkrix:module-seed Blog
 */
class SeedCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述、参数
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-seed')
            ->setDescription('执行模块数据填充')
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
        $module = $input->getArgument('module');
        $generator = $this->getGenerator();

        // 将模块名转换为 StudlyCase
        $moduleName = $generator->studlyCase($module);

        // 验证模块存在
        if (!$this->validateModuleExists($moduleName, $output)) {
            return 1;
        }

        $modulePath = $generator->getModulePath($moduleName);
        $seederPath = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders';

        if (!is_dir($seederPath)) {
            $output->writeln("<comment>Module [{$moduleName}] has no seeders directory.</comment>");
            return 0;
        }

        // 获取 seeder 文件
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
}

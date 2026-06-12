<?php

namespace Thinkrix\Commands\Module;

use think\console\Command;
use think\console\Output;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * 模块命令抽象基类
 *
 * 为所有模块相关命令提供通用的辅助方法，
 * 包括生成器获取、模板解析器获取、模块校验等功能。
 */
abstract class BaseModuleCommand extends Command
{
    /**
     * ModuleGenerator 实例缓存
     */
    protected ?ModuleGenerator $generator = null;

    /**
     * StubResolver 实例缓存
     */
    protected ?StubResolver $stubResolver = null;

    /**
     * 获取模块生成器实例
     *
     * 创建并缓存 ModuleGenerator 实例，避免重复实例化。
     *
     * @return ModuleGenerator
     */
    protected function getGenerator(): ModuleGenerator
    {
        if ($this->generator === null) {
            $this->generator = new ModuleGenerator();
        }

        return $this->generator;
    }

    /**
     * 获取 Stub 模板解析器实例
     *
     * 创建并缓存 StubResolver 实例，避免重复实例化。
     *
     * @return StubResolver
     */
    protected function getStubResolver(): StubResolver
    {
        if ($this->stubResolver === null) {
            $this->stubResolver = new StubResolver();
        }

        return $this->stubResolver;
    }

    /**
     * 验证模块是否存在
     *
     * 检查指定模块是否已存在于文件系统中。
     * 如果模块不存在，将输出错误信息并返回 false。
     *
     * @param string $module 模块名称（StudlyCase）
     * @param Output $output 输出实例
     * @return bool 模块存在返回 true，不存在返回 false
     */
    protected function validateModuleExists(string $module, Output $output): bool
    {
        if (!$this->getGenerator()->moduleExists($module)) {
            $output->writeln("<error>Module [{$module}] does not exist.</error>");
            return false;
        }

        return true;
    }

    /**
     * 获取模块路径
     *
     * 委托给 ModuleGenerator 获取模块在 app/ 下的完整路径。
     *
     * @param string $module 模块名称（StudlyCase）
     * @return string 模块的完整路径
     */
    protected function getModulePath(string $module): string
    {
        return $this->getGenerator()->getModulePath($module);
    }
}

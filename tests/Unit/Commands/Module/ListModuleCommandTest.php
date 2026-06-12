<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\ListModuleCommand;

/**
 * ListModuleCommand 单元测试
 *
 * 验证命令配置正确性和表格输出逻辑。
 *
 * Requirements: 3.4
 */
class ListModuleCommandTest extends TestCase
{
    /**
     * 测试命令名称配置正确
     */
    public function testCommandNameIsCorrect(): void
    {
        $command = new ListModuleCommand();
        $this->assertEquals('thinkrix:module-list', $command->getName());
    }

    /**
     * 测试命令描述已配置
     */
    public function testCommandHasDescription(): void
    {
        $command = new ListModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 测试命令不需要任何参数
     */
    public function testCommandHasNoArguments(): void
    {
        $command = new ListModuleCommand();
        $definition = $command->getDefinition();

        $this->assertCount(0, $definition->getArguments());
    }

    /**
     * 测试命令不需要任何自定义选项（除内置选项外）
     */
    public function testCommandHasNoCustomOptions(): void
    {
        $command = new ListModuleCommand();
        $definition = $command->getDefinition();

        // ThinkPHP/Symfony 内置选项列表
        $builtinOptions = ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction'];

        $options = $definition->getOptions();
        $customOptions = array_filter($options, function ($option) use ($builtinOptions) {
            return !in_array($option->getName(), $builtinOptions);
        });

        $this->assertEmpty($customOptions, '命令不应有自定义选项');
    }
}

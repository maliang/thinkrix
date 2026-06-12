<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\EnableModuleCommand;
use Thinkrix\Commands\Module\DisableModuleCommand;
use Thinkrix\Commands\Module\DeleteModuleCommand;
use Thinkrix\Commands\Module\ListModuleCommand;
use Thinkrix\Commands\Module\BaseModuleCommand;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * 模块生命周期管理命令单元测试
 *
 * 测试启用/禁用/删除/列表命令的配置正确性、类继承结构，
 * 以及 DeleteModuleCommand 的目录删除逻辑。
 *
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6
 */
class LifecycleCommandsTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;
    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建临时目录模拟项目结构
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lifecycle_cmd_test_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'app', 0755, true);

        // 创建基础 stub 模板
        $this->createStubFiles();

        // 使用可测试的 StubResolver 和 ModuleGenerator
        $stubResolver = $this->createStubResolver();
        $this->generator = $this->createGenerator($stubResolver);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== EnableModuleCommand 配置测试 ====================

    /**
     * 测试 EnableModuleCommand 命令名称配置正确
     *
     * Requirements: 3.1
     */
    public function testEnableCommandNameIsCorrect(): void
    {
        $command = new EnableModuleCommand();
        $this->assertEquals('thinkrix:module-enable', $command->getName());
    }

    /**
     * 测试 EnableModuleCommand 定义了必须的 name 参数
     *
     * Requirements: 3.1, 3.5
     */
    public function testEnableCommandHasRequiredNameArgument(): void
    {
        $command = new EnableModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('name'));
        $argument = $definition->getArgument('name');
        $this->assertTrue($argument->isRequired());
    }

    /**
     * 测试 EnableModuleCommand 有描述信息
     *
     * Requirements: 3.1
     */
    public function testEnableCommandHasDescription(): void
    {
        $command = new EnableModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 测试 EnableModuleCommand 继承自 BaseModuleCommand
     *
     * Requirements: 3.1
     */
    public function testEnableCommandExtendsBaseModuleCommand(): void
    {
        $command = new EnableModuleCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    // ==================== DisableModuleCommand 配置测试 ====================

    /**
     * 测试 DisableModuleCommand 命令名称配置正确
     *
     * Requirements: 3.2
     */
    public function testDisableCommandNameIsCorrect(): void
    {
        $command = new DisableModuleCommand();
        $this->assertEquals('thinkrix:module-disable', $command->getName());
    }

    /**
     * 测试 DisableModuleCommand 定义了必须的 name 参数
     *
     * Requirements: 3.2, 3.5
     */
    public function testDisableCommandHasRequiredNameArgument(): void
    {
        $command = new DisableModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('name'));
        $argument = $definition->getArgument('name');
        $this->assertTrue($argument->isRequired());
    }

    /**
     * 测试 DisableModuleCommand 有描述信息
     *
     * Requirements: 3.2
     */
    public function testDisableCommandHasDescription(): void
    {
        $command = new DisableModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 测试 DisableModuleCommand 继承自 BaseModuleCommand
     *
     * Requirements: 3.2
     */
    public function testDisableCommandExtendsBaseModuleCommand(): void
    {
        $command = new DisableModuleCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    // ==================== DeleteModuleCommand 配置测试 ====================

    /**
     * 测试 DeleteModuleCommand 命令名称配置正确
     *
     * Requirements: 3.3
     */
    public function testDeleteCommandNameIsCorrect(): void
    {
        $command = new DeleteModuleCommand();
        $this->assertEquals('thinkrix:module-delete', $command->getName());
    }

    /**
     * 测试 DeleteModuleCommand 定义了必须的 name 参数
     *
     * Requirements: 3.3, 3.5
     */
    public function testDeleteCommandHasRequiredNameArgument(): void
    {
        $command = new DeleteModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('name'));
        $argument = $definition->getArgument('name');
        $this->assertTrue($argument->isRequired());
    }

    /**
     * 测试 DeleteModuleCommand 有描述信息
     *
     * Requirements: 3.3, 3.6
     */
    public function testDeleteCommandHasDescription(): void
    {
        $command = new DeleteModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 测试 DeleteModuleCommand 继承自 BaseModuleCommand
     *
     * Requirements: 3.3
     */
    public function testDeleteCommandExtendsBaseModuleCommand(): void
    {
        $command = new DeleteModuleCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    // ==================== ListModuleCommand 配置测试 ====================

    /**
     * 测试 ListModuleCommand 命令名称配置正确
     *
     * Requirements: 3.4
     */
    public function testListCommandNameIsCorrect(): void
    {
        $command = new ListModuleCommand();
        $this->assertEquals('thinkrix:module-list', $command->getName());
    }

    /**
     * 测试 ListModuleCommand 有描述信息
     *
     * Requirements: 3.4
     */
    public function testListCommandHasDescription(): void
    {
        $command = new ListModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 测试 ListModuleCommand 没有必须的参数
     *
     * Requirements: 3.4
     */
    public function testListCommandHasNoRequiredArguments(): void
    {
        $command = new ListModuleCommand();
        $definition = $command->getDefinition();
        $arguments = $definition->getArguments();

        // ListModuleCommand 不应定义任何参数
        $requiredArgs = array_filter($arguments, fn($arg) => $arg->isRequired());
        $this->assertEmpty($requiredArgs, 'ListModuleCommand should not have any required arguments');
    }

    /**
     * 测试 ListModuleCommand 继承自 BaseModuleCommand
     *
     * Requirements: 3.4
     */
    public function testListCommandExtendsBaseModuleCommand(): void
    {
        $command = new ListModuleCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    // ==================== DeleteModuleCommand 目录删除逻辑测试 ====================

    /**
     * 测试通过 ModuleGenerator 验证模块存在性（删除前检查）
     *
     * 模拟 DeleteModuleCommand 中 validateModuleExists 的逻辑：
     * 模块目录存在时 moduleExists 返回 true
     *
     * Requirements: 3.3, 3.5
     */
    public function testModuleExistsBeforeDeletion(): void
    {
        // 创建模块目录模拟已存在的模块
        $moduleName = 'TestModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        // moduleExists 应返回 true
        $this->assertTrue($this->generator->moduleExists($moduleName));
    }

    /**
     * 测试模块不存在时 moduleExists 返回 false（模拟错误提示场景）
     *
     * Requirements: 3.5
     */
    public function testModuleDoesNotExistReturnsError(): void
    {
        $moduleName = 'NonExistentModule';

        // moduleExists 应返回 false
        $this->assertFalse($this->generator->moduleExists($moduleName));
    }

    /**
     * 测试 removeDirectory 逻辑：递归删除包含文件和子目录的模块目录
     *
     * 通过反射调用 DeleteModuleCommand 的 removeDirectory 私有方法，
     * 验证其能正确递归删除目录结构。
     *
     * Requirements: 3.3, 3.6
     */
    public function testRemoveDirectoryDeletesRecursively(): void
    {
        $command = new DeleteModuleCommand();

        // 创建嵌套目录结构模拟模块目录
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'module_to_delete';
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'controller', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'model', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations', 0755, true);

        // 创建一些文件
        file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'module.json', '{"name": "Test"}');
        file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php', '<?php');
        file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . 'User.php', '<?php');
        file_put_contents(
            $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '001_create_table.php',
            '<?php'
        );

        // 确认目录和文件存在
        $this->assertDirectoryExists($modulePath);
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php');

        // 通过反射调用 removeDirectory 私有方法
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('removeDirectory');
        $method->setAccessible(true);
        $method->invoke($command, $modulePath);

        // 验证目录已被完全删除
        $this->assertDirectoryDoesNotExist($modulePath);
    }

    /**
     * 测试 removeDirectory 对不存在的目录不会报错
     *
     * Requirements: 3.3
     */
    public function testRemoveDirectoryHandlesNonExistentPath(): void
    {
        $command = new DeleteModuleCommand();
        $nonExistentPath = $this->tempDir . DIRECTORY_SEPARATOR . 'non_existent_dir';

        // 通过反射调用 removeDirectory，不应抛出异常
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('removeDirectory');
        $method->setAccessible(true);

        // 不应抛出任何异常
        $method->invoke($command, $nonExistentPath);
        $this->assertDirectoryDoesNotExist($nonExistentPath);
    }

    /**
     * 测试删除前后 moduleExists 状态变化
     *
     * 模拟完整的删除流程：模块存在 → 删除目录 → 模块不存在
     *
     * Requirements: 3.3, 3.5, 3.6
     */
    public function testModuleExistsStateChangeAfterDeletion(): void
    {
        $moduleName = 'ModuleToDelete';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        // 创建模块目录
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'controller', 0755, true);
        file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'module.json', '{"name": "ModuleToDelete"}');

        // 删除前：模块存在
        $this->assertTrue($this->generator->moduleExists($moduleName));

        // 模拟删除操作（递归删除目录）
        $this->removeDirectory($modulePath);

        // 删除后：模块不存在
        $this->assertFalse($this->generator->moduleExists($moduleName));
    }

    /**
     * 测试名称转换在生命周期命令中的使用
     *
     * 验证 studlyCase 转换与 moduleExists 检查的配合使用，
     * 这是 Enable/Disable/Delete 命令共用的执行流程。
     *
     * Requirements: 3.1, 3.2, 3.3, 3.5
     */
    public function testNameConversionForLifecycleCommands(): void
    {
        // 创建 UserCenter 模块目录
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        mkdir($modulePath, 0755, true);

        // 模拟命令流程：kebab-case 输入 → studlyCase 转换 → 检查存在性
        $studlyName = $this->generator->studlyCase('user-center');
        $this->assertEquals('UserCenter', $studlyName);
        $this->assertTrue($this->generator->moduleExists($studlyName));

        // snake_case 输入同样可以转换
        $studlyName2 = $this->generator->studlyCase('user_center');
        $this->assertEquals('UserCenter', $studlyName2);
        $this->assertTrue($this->generator->moduleExists($studlyName2));
    }

    // ==================== 辅助方法 ====================

    /**
     * 创建基础 Stub 文件
     */
    private function createStubFiles(): void
    {
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'module.json.stub',
            '{"name": "{{MODULE_NAME}}", "alias": "{{LOWER_NAME}}", "enabled": true}'
        );
    }

    /**
     * 创建可测试的 StubResolver 实例
     */
    private function createStubResolver(): StubResolver
    {
        return new class($this->packageStubDir, $this->customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };
    }

    /**
     * 创建可测试的 ModuleGenerator 实例（覆盖 getModulePath 使用临时目录）
     */
    private function createGenerator(StubResolver $stubResolver): ModuleGenerator
    {
        $tempDir = $this->tempDir;
        return new class($stubResolver, $tempDir) extends ModuleGenerator {
            private string $rootPath;

            public function __construct(StubResolver $stubResolver, string $rootPath)
            {
                parent::__construct($stubResolver);
                $this->rootPath = $rootPath . DIRECTORY_SEPARATOR;
            }

            public function getModulePath(string $module): string
            {
                return $this->rootPath . 'app' . DIRECTORY_SEPARATOR . $module;
            }
        };
    }

    /**
     * 递归删除目录
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}

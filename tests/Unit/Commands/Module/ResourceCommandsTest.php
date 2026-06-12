<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\MakeControllerCommand;
use Thinkrix\Commands\Module\MakeModelCommand;
use Thinkrix\Commands\Module\MakeServiceCommand;
use Thinkrix\Commands\Module\MakeMigrationCommand;
use Thinkrix\Commands\Module\MakeSeederCommand;
use Thinkrix\Commands\Module\MakeValidateCommand;
use Thinkrix\Commands\Module\MakeMiddlewareCommand;
use Thinkrix\Commands\Module\MakeEventCommand;
use Thinkrix\Commands\Module\MakeListenerCommand;
use Thinkrix\Commands\Module\MakeCommandCommand;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * 资源生成命令单元测试
 *
 * 测试所有模块内资源生成命令的配置正确性、文件生成与命名空间设置。
 * 使用临时目录模拟项目结构，避免依赖 ThinkPHP app() 容器。
 *
 * Requirements: 2.1-2.11
 */
class ResourceCommandsTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;
    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建临时目录模拟项目结构
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource_cmd_test_' . uniqid();
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

    // ==================== 命令配置测试 ====================

    /**
     * 测试 MakeControllerCommand 命令配置
     *
     * Requirements: 2.1
     */
    public function testMakeControllerCommandConfiguration(): void
    {
        $command = new MakeControllerCommand();
        $this->assertEquals('thinkrix:module-make-controller', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
        $this->assertTrue($definition->getArgument('name')->isRequired());
        $this->assertTrue($definition->getArgument('module')->isRequired());
    }

    /**
     * 测试 MakeModelCommand 命令配置
     *
     * Requirements: 2.2
     */
    public function testMakeModelCommandConfiguration(): void
    {
        $command = new MakeModelCommand();
        $this->assertEquals('thinkrix:module-make-model', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
        $this->assertTrue($definition->getArgument('name')->isRequired());
        $this->assertTrue($definition->getArgument('module')->isRequired());
    }

    /**
     * 测试 MakeServiceCommand 命令配置
     *
     * Requirements: 2.3
     */
    public function testMakeServiceCommandConfiguration(): void
    {
        $command = new MakeServiceCommand();
        $this->assertEquals('thinkrix:module-make-service', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 测试 MakeMigrationCommand 命令配置
     *
     * Requirements: 2.4
     */
    public function testMakeMigrationCommandConfiguration(): void
    {
        $command = new MakeMigrationCommand();
        $this->assertEquals('thinkrix:module-make-migration', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 测试 MakeSeederCommand 命令配置
     *
     * Requirements: 2.5
     */
    public function testMakeSeederCommandConfiguration(): void
    {
        $command = new MakeSeederCommand();
        $this->assertEquals('thinkrix:module-make-seeder', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 测试 MakeValidateCommand 命令配置
     *
     * Requirements: 2.6
     */
    public function testMakeValidateCommandConfiguration(): void
    {
        $command = new MakeValidateCommand();
        $this->assertEquals('thinkrix:module-make-validate', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 测试 MakeMiddlewareCommand 命令配置
     *
     * Requirements: 2.7
     */
    public function testMakeMiddlewareCommandConfiguration(): void
    {
        $command = new MakeMiddlewareCommand();
        $this->assertEquals('thinkrix:module-make-middleware', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 测试 MakeEventCommand 命令配置
     *
     * Requirements: 2.8
     */
    public function testMakeEventCommandConfiguration(): void
    {
        $command = new MakeEventCommand();
        $this->assertEquals('thinkrix:module-make-event', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 测试 MakeListenerCommand 命令配置
     *
     * Requirements: 2.9
     */
    public function testMakeListenerCommandConfiguration(): void
    {
        $command = new MakeListenerCommand();
        $this->assertEquals('thinkrix:module-make-listener', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 测试 MakeCommandCommand 命令配置
     *
     * Requirements: 2.10
     */
    public function testMakeCommandCommandConfiguration(): void
    {
        $command = new MakeCommandCommand();
        $this->assertEquals('thinkrix:module-make-command', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    // ==================== 控制器生成测试 ====================

    /**
     * 测试控制器文件生成到正确目录
     *
     * Requirements: 2.1, 2.11
     */
    public function testGenerateControllerCreatesFileInCorrectDirectory(): void
    {
        // 预创建模块目录
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'controller', 'UserController');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('controller', $filePath);
    }

    /**
     * 测试控制器命名空间正确
     *
     * Requirements: 2.1, 2.11
     */
    public function testGenerateControllerHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 使用连字符分隔的名称，studlyCase 会正确转换为 UserController
        $filePath = $this->generator->generateResource('Blog', 'controller', 'user-controller');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\controller', $content);
        $this->assertStringContainsString('UserController', $content);
    }

    // ==================== 模型生成测试 ====================

    /**
     * 测试模型文件生成到正确目录
     *
     * Requirements: 2.2, 2.11
     */
    public function testGenerateModelCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'model', 'User');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('model', $filePath);
    }

    /**
     * 测试模型命名空间正确
     *
     * Requirements: 2.2, 2.11
     */
    public function testGenerateModelHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'model', 'User');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\model', $content);
        $this->assertStringContainsString('User', $content);
    }

    // ==================== 服务生成测试 ====================

    /**
     * 测试服务文件生成到正确目录
     *
     * Requirements: 2.3, 2.11
     */
    public function testGenerateServiceCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'service', 'UserService');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('service', $filePath);
    }

    /**
     * 测试服务命名空间正确
     *
     * Requirements: 2.3, 2.11
     */
    public function testGenerateServiceHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 使用连字符分隔的名称，studlyCase 会正确转换为 UserService
        $filePath = $this->generator->generateResource('Blog', 'service', 'user-service');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\service', $content);
        $this->assertStringContainsString('UserService', $content);
    }

    // ==================== 迁移生成测试 ====================

    /**
     * 测试迁移文件生成到正确目录
     *
     * Requirements: 2.4, 2.11
     */
    public function testGenerateMigrationCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'migration', 'create_posts');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('database' . DIRECTORY_SEPARATOR . 'migrations', $filePath);
    }

    /**
     * 测试迁移文件名包含时间戳前缀
     *
     * Requirements: 2.4
     */
    public function testGenerateMigrationFileNameHasTimestampPrefix(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'migration', 'create_posts');
        $filename = basename($filePath);

        // 文件名格式：{YmdHis}_create_{table_name}_table.php
        $this->assertMatchesRegularExpression('/^\d{14}_create_/', $filename);
        $this->assertStringEndsWith('.php', $filename);
    }

    /**
     * 测试迁移命名空间为 database 层级
     *
     * Requirements: 2.4, 2.11
     */
    public function testGenerateMigrationHasDatabaseNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'migration', 'create_posts');
        $content = file_get_contents($filePath);

        // migration 的命名空间应为 app\{Module}\database
        $this->assertStringContainsString('app\\Blog\\database', $content);
    }

    // ==================== Seeder 生成测试 ====================

    /**
     * 测试 Seeder 文件生成到正确目录
     *
     * Requirements: 2.5, 2.11
     */
    public function testGenerateSeederCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'seeder', 'UserSeeder');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('database' . DIRECTORY_SEPARATOR . 'seeders', $filePath);
    }

    /**
     * 测试 Seeder 命名空间为 database 层级
     *
     * Requirements: 2.5, 2.11
     */
    public function testGenerateSeederHasDatabaseNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'seeder', 'UserSeeder');
        $content = file_get_contents($filePath);

        // seeder 的命名空间应为 app\{Module}\database
        $this->assertStringContainsString('app\\Blog\\database', $content);
    }

    // ==================== 验证器生成测试 ====================

    /**
     * 测试验证器文件生成到正确目录
     *
     * Requirements: 2.6, 2.11
     */
    public function testGenerateValidateCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'validate', 'UserValidate');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('validate', $filePath);
    }

    /**
     * 测试验证器命名空间正确
     *
     * Requirements: 2.6, 2.11
     */
    public function testGenerateValidateHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 使用连字符分隔的名称，studlyCase 会正确转换为 UserValidate
        $filePath = $this->generator->generateResource('Blog', 'validate', 'user-validate');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\validate', $content);
        $this->assertStringContainsString('UserValidate', $content);
    }

    // ==================== 中间件生成测试 ====================

    /**
     * 测试中间件文件生成到正确目录
     *
     * Requirements: 2.7, 2.11
     */
    public function testGenerateMiddlewareCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'middleware', 'CheckAuth');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('middleware', $filePath);
    }

    /**
     * 测试中间件命名空间正确
     *
     * Requirements: 2.7, 2.11
     */
    public function testGenerateMiddlewareHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 使用连字符分隔的名称，studlyCase 会正确转换为 CheckAuth
        $filePath = $this->generator->generateResource('Blog', 'middleware', 'check-auth');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\middleware', $content);
        $this->assertStringContainsString('CheckAuth', $content);
    }

    // ==================== 事件生成测试 ====================

    /**
     * 测试事件文件生成到正确目录
     *
     * Requirements: 2.8, 2.11
     */
    public function testGenerateEventCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'event', 'UserCreated');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('event', $filePath);
    }

    /**
     * 测试事件命名空间正确
     *
     * Requirements: 2.8, 2.11
     */
    public function testGenerateEventHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 使用连字符分隔的名称，studlyCase 会正确转换为 UserCreated
        $filePath = $this->generator->generateResource('Blog', 'event', 'user-created');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\event', $content);
        $this->assertStringContainsString('UserCreated', $content);
    }

    // ==================== 监听器生成测试 ====================

    /**
     * 测试监听器文件生成到正确目录
     *
     * Requirements: 2.9, 2.11
     */
    public function testGenerateListenerCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'listener', 'SendNotification');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('listener', $filePath);
    }

    /**
     * 测试监听器命名空间正确
     *
     * Requirements: 2.9, 2.11
     */
    public function testGenerateListenerHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 使用连字符分隔的名称，studlyCase 会正确转换为 SendNotification
        $filePath = $this->generator->generateResource('Blog', 'listener', 'send-notification');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\listener', $content);
        $this->assertStringContainsString('SendNotification', $content);
    }

    // ==================== 命令文件生成测试 ====================

    /**
     * 测试命令文件生成到正确目录
     *
     * Requirements: 2.10, 2.11
     */
    public function testGenerateCommandCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'command', 'sync-data');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('command', $filePath);
    }

    /**
     * 测试命令文件命名空间正确
     *
     * Requirements: 2.10, 2.11
     */
    public function testGenerateCommandHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'command', 'sync-data');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\command', $content);
        // sync-data 经 studlyCase 转换为 SyncData
        $this->assertStringContainsString('SyncData', $content);
    }

    // ==================== 模块不存在时的错误处理测试 ====================

    /**
     * 测试目标模块不存在时 generateResource 返回空字符串（controller）
     *
     * Requirements: 2.10
     */
    public function testGenerateControllerReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'controller', 'UserController');
        $this->assertEmpty($result);
    }

    /**
     * 测试目标模块不存在时 generateResource 返回空字符串（model）
     *
     * Requirements: 2.10
     */
    public function testGenerateModelReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'model', 'User');
        $this->assertEmpty($result);
    }

    /**
     * 测试目标模块不存在时 generateResource 返回空字符串（service）
     *
     * Requirements: 2.10
     */
    public function testGenerateServiceReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'service', 'UserService');
        $this->assertEmpty($result);
    }

    /**
     * 测试目标模块不存在时 generateResource 返回空字符串（migration）
     *
     * Requirements: 2.10
     */
    public function testGenerateMigrationReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'migration', 'create_posts');
        $this->assertEmpty($result);
    }

    /**
     * 测试目标模块不存在时 generateResource 返回空字符串（seeder）
     *
     * Requirements: 2.10
     */
    public function testGenerateSeederReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'seeder', 'UserSeeder');
        $this->assertEmpty($result);
    }

    /**
     * 测试目标模块不存在时 generateResource 返回空字符串（validate）
     *
     * Requirements: 2.10
     */
    public function testGenerateValidateReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'validate', 'UserValidate');
        $this->assertEmpty($result);
    }

    /**
     * 测试目标模块不存在时 generateResource 返回空字符串（middleware）
     *
     * Requirements: 2.10
     */
    public function testGenerateMiddlewareReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'middleware', 'CheckAuth');
        $this->assertEmpty($result);
    }

    /**
     * 测试目标模块不存在时 generateResource 返回空字符串（event）
     *
     * Requirements: 2.10
     */
    public function testGenerateEventReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'event', 'UserCreated');
        $this->assertEmpty($result);
    }

    /**
     * 测试目标模块不存在时 generateResource 返回空字符串（listener）
     *
     * Requirements: 2.10
     */
    public function testGenerateListenerReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'listener', 'SendNotification');
        $this->assertEmpty($result);
    }

    /**
     * 测试目标模块不存在时 generateResource 返回空字符串（command）
     *
     * Requirements: 2.10
     */
    public function testGenerateCommandReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'command', 'sync-data');
        $this->assertEmpty($result);
    }

    // ==================== 名称转换测试 ====================

    /**
     * 测试资源名称经 StudlyCase 转换后作为类名
     *
     * Requirements: 2.11
     */
    public function testResourceNameIsConvertedToStudlyCase(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'controller', 'user-profile');
        $content = file_get_contents($filePath);

        // user-profile 经 studlyCase 转为 UserProfile
        $this->assertStringContainsString('UserProfile', $content);
    }

    /**
     * 测试多段模块名（带连字符）的命名空间正确性
     *
     * Requirements: 2.11
     */
    public function testResourceInMultiWordModuleHasCorrectNamespace(): void
    {
        // 注意：generateResource 内部对 module 参数也会调用 studlyCase
        // 所以传入 'user-center' 会被转换为 'UserCenter'
        // 但 getModulePath 使用转换后的名称检查目录是否存在
        $this->createModuleDirectory('UserCenter');

        // 使用连字符格式的模块名，generateResource 内部会 studlyCase 转换
        $filePath = $this->generator->generateResource('user-center', 'service', 'order-service');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\UserCenter\\service', $content);
        $this->assertStringContainsString('OrderService', $content);
    }

    // ==================== 辅助方法 ====================

    /**
     * 在临时目录中创建模块目录结构
     */
    private function createModuleDirectory(string $moduleName): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'controller', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'model', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'service', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'validate', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'middleware', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'event', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'listener', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'command', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders', 0755, true);
    }

    /**
     * 创建基础 Stub 文件
     */
    private function createStubFiles(): void
    {
        // controller.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // controller.plain.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.plain.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // model.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'model.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // service.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'service.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // migration.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'migration.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // seeder.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'seeder.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // validate.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'validate.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // middleware.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'middleware.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // event.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'event.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // listener.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'listener.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // command.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'command.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
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

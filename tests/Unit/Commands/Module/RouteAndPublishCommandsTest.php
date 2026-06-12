<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\RouteListCommand;
use Thinkrix\Commands\Module\PublishStubsCommand;
use Thinkrix\Commands\Module\PublishConfigCommand;
use Thinkrix\Commands\Module\BaseModuleCommand;
use Thinkrix\Support\StubResolver;
use ReflectionMethod;

/**
 * 路由管理与配置发布命令单元测试
 *
 * 测试路由列表输出、Stub 发布到正确目录、配置发布与文件复制。
 *
 * Requirements: 5.4, 6.1, 7.3
 */
class RouteAndPublishCommandsTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建临时目录模拟项目结构
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'route_publish_test_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== RouteListCommand 配置测试 ====================

    /**
     * 测试 RouteListCommand 命令名称正确
     *
     * Requirements: 5.4
     */
    public function testRouteListCommandName(): void
    {
        $command = new RouteListCommand();
        $this->assertEquals('thinkrix:module-route-list', $command->getName());
    }

    /**
     * 测试 RouteListCommand 有必需的 module 参数
     *
     * Requirements: 5.4
     */
    public function testRouteListCommandHasModuleArgument(): void
    {
        $command = new RouteListCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('module'));
        $this->assertTrue($definition->getArgument('module')->isRequired());
    }

    /**
     * 测试 RouteListCommand 有描述信息
     *
     * Requirements: 5.4
     */
    public function testRouteListCommandHasDescription(): void
    {
        $command = new RouteListCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 测试 RouteListCommand 继承 BaseModuleCommand
     *
     * Requirements: 5.4
     */
    public function testRouteListCommandExtendsBaseModuleCommand(): void
    {
        $command = new RouteListCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    /**
     * 测试 parseRouteDefinitions 解析 Route::get 模式
     *
     * Requirements: 5.4
     */
    public function testParseRouteDefinitionsMatchesGetRoute(): void
    {
        $command = new RouteListCommand();
        $method = new ReflectionMethod($command, 'parseRouteDefinitions');
        $method->setAccessible(true);

        $content = "Route::get('users', 'UserController@index');";
        $result = $method->invoke($command, $content);

        $this->assertCount(1, $result);
        $this->assertEquals(['GET', 'users', 'UserController@index'], $result[0]);
    }

    /**
     * 测试 parseRouteDefinitions 解析 Route::post 模式
     *
     * Requirements: 5.4
     */
    public function testParseRouteDefinitionsMatchesPostRoute(): void
    {
        $command = new RouteListCommand();
        $method = new ReflectionMethod($command, 'parseRouteDefinitions');
        $method->setAccessible(true);

        $content = "Route::post('users/create', 'UserController@store');";
        $result = $method->invoke($command, $content);

        $this->assertCount(1, $result);
        $this->assertEquals(['POST', 'users/create', 'UserController@store'], $result[0]);
    }

    /**
     * 测试 parseRouteDefinitions 解析多种 HTTP 方法
     *
     * Requirements: 5.4
     */
    public function testParseRouteDefinitionsMatchesMultipleMethods(): void
    {
        $command = new RouteListCommand();
        $method = new ReflectionMethod($command, 'parseRouteDefinitions');
        $method->setAccessible(true);

        $content = <<<'PHP'
Route::get('users', 'UserController@index');
Route::post('users', 'UserController@store');
Route::put('users/:id', 'UserController@update');
Route::delete('users/:id', 'UserController@destroy');
PHP;

        $result = $method->invoke($command, $content);

        $this->assertCount(4, $result);
        $this->assertEquals('GET', $result[0][0]);
        $this->assertEquals('POST', $result[1][0]);
        $this->assertEquals('PUT', $result[2][0]);
        $this->assertEquals('DELETE', $result[3][0]);
    }

    /**
     * 测试 parseRouteDefinitions 对 group 模式返回空数组
     *
     * Requirements: 5.4
     */
    public function testParseRouteDefinitionsReturnsEmptyForGroupPatterns(): void
    {
        $command = new RouteListCommand();
        $method = new ReflectionMethod($command, 'parseRouteDefinitions');
        $method->setAccessible(true);

        $content = <<<'PHP'
Route::group('api', function () {
    // 嵌套路由
});
PHP;

        $result = $method->invoke($command, $content);

        $this->assertEmpty($result);
    }

    /**
     * 测试 parseRouteDefinitions 对空内容返回空数组
     *
     * Requirements: 5.4
     */
    public function testParseRouteDefinitionsReturnsEmptyForEmptyContent(): void
    {
        $command = new RouteListCommand();
        $method = new ReflectionMethod($command, 'parseRouteDefinitions');
        $method->setAccessible(true);

        $result = $method->invoke($command, '');

        $this->assertEmpty($result);
    }

    // ==================== PublishStubsCommand 配置测试 ====================

    /**
     * 测试 PublishStubsCommand 命令名称正确
     *
     * Requirements: 6.1
     */
    public function testPublishStubsCommandName(): void
    {
        $command = new PublishStubsCommand();
        $this->assertEquals('thinkrix:module-publish-stubs', $command->getName());
    }

    /**
     * 测试 PublishStubsCommand 有描述信息
     *
     * Requirements: 6.1
     */
    public function testPublishStubsCommandHasDescription(): void
    {
        $command = new PublishStubsCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 测试 PublishStubsCommand 没有必需参数
     *
     * Requirements: 6.1
     */
    public function testPublishStubsCommandHasNoRequiredArguments(): void
    {
        $command = new PublishStubsCommand();
        $definition = $command->getDefinition();
        $arguments = $definition->getArguments();

        // 命令不应有任何必需参数
        $requiredArguments = array_filter($arguments, fn($arg) => $arg->isRequired());
        $this->assertEmpty($requiredArguments, 'PublishStubsCommand should have no required arguments');
    }

    /**
     * 测试 PublishStubsCommand 继承 BaseModuleCommand
     *
     * Requirements: 6.1
     */
    public function testPublishStubsCommandExtendsBaseModuleCommand(): void
    {
        $command = new PublishStubsCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    /**
     * 测试 StubResolver::publishStubs 将文件复制到自定义目录
     *
     * Requirements: 6.1
     */
    public function testStubResolverPublishStubsCopiesFilesToCustomDir(): void
    {
        // 创建测试 stub 文件
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'model.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'service.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // 创建可测试的 StubResolver 实例
        $stubResolver = new class($this->packageStubDir, $this->customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };

        // 执行发布
        $published = $stubResolver->publishStubs();

        // 验证结果
        $this->assertNotEmpty($published);
        $this->assertCount(3, $published);

        // 验证文件确实被复制到自定义目录
        $this->assertArrayHasKey('controller.stub', $published);
        $this->assertArrayHasKey('model.stub', $published);
        $this->assertArrayHasKey('service.stub', $published);

        // 验证目标文件存在
        foreach ($published as $filename => $targetPath) {
            $this->assertFileExists($targetPath);
            $this->assertStringContainsString($this->customStubDir, $targetPath);
        }
    }

    /**
     * 测试 StubResolver::publishStubs 在无 stub 文件时返回空数组
     *
     * Requirements: 6.1
     */
    public function testStubResolverPublishStubsReturnsEmptyWhenNoStubs(): void
    {
        // 创建一个空的 stub 目录
        $emptyStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'empty_stubs';
        mkdir($emptyStubDir, 0755, true);

        $stubResolver = new class($emptyStubDir, $this->customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };

        $published = $stubResolver->publishStubs();

        $this->assertEmpty($published);
    }

    /**
     * 测试 StubResolver::publishStubs 不覆盖已存在的自定义文件
     *
     * Requirements: 6.1
     */
    public function testStubResolverPublishStubsDoesNotOverwriteExisting(): void
    {
        // 创建默认 stub 文件
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub',
            "default content"
        );

        // 创建已存在的自定义 stub 文件
        mkdir($this->customStubDir, 0755, true);
        file_put_contents(
            $this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub',
            "custom content"
        );

        $stubResolver = new class($this->packageStubDir, $this->customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };

        $stubResolver->publishStubs();

        // 验证自定义文件内容未被覆盖
        $content = file_get_contents($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub');
        $this->assertEquals("custom content", $content);
    }

    // ==================== PublishConfigCommand 配置测试 ====================

    /**
     * 测试 PublishConfigCommand 命令名称正确
     *
     * Requirements: 7.3
     */
    public function testPublishConfigCommandName(): void
    {
        $command = new PublishConfigCommand();
        $this->assertEquals('thinkrix:module-publish-config', $command->getName());
    }

    /**
     * 测试 PublishConfigCommand 有必需的 module 参数
     *
     * Requirements: 7.3
     */
    public function testPublishConfigCommandHasModuleArgument(): void
    {
        $command = new PublishConfigCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('module'));
        $this->assertTrue($definition->getArgument('module')->isRequired());
    }

    /**
     * 测试 PublishConfigCommand 有描述信息
     *
     * Requirements: 7.3
     */
    public function testPublishConfigCommandHasDescription(): void
    {
        $command = new PublishConfigCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 测试 PublishConfigCommand 继承 BaseModuleCommand
     *
     * Requirements: 7.3
     */
    public function testPublishConfigCommandExtendsBaseModuleCommand(): void
    {
        $command = new PublishConfigCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    /**
     * 测试配置文件可被正确读取与复制
     *
     * Requirements: 7.3
     */
    public function testConfigFileCanBeReadAndCopied(): void
    {
        // 模拟模块配置文件
        $moduleConfigDir = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog' . DIRECTORY_SEPARATOR . 'config';
        mkdir($moduleConfigDir, 0755, true);

        $configContent = <<<'PHP'
<?php
return [
    'name' => 'Blog',
    'version' => '1.0.0',
    'enabled' => true,
];
PHP;

        $sourceFile = $moduleConfigDir . DIRECTORY_SEPARATOR . 'config.php';
        file_put_contents($sourceFile, $configContent);

        // 模拟目标目录
        $targetDir = $this->tempDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules';
        mkdir($targetDir, 0755, true);

        $targetFile = $targetDir . DIRECTORY_SEPARATOR . 'blog.php';

        // 执行复制（模拟 PublishConfigCommand 的核心逻辑）
        copy($sourceFile, $targetFile);

        // 验证文件被正确复制
        $this->assertFileExists($targetFile);

        // 验证内容一致
        $copiedContent = file_get_contents($targetFile);
        $this->assertEquals($configContent, $copiedContent);

        // 验证配置可被正确加载
        $config = include $targetFile;
        $this->assertIsArray($config);
        $this->assertEquals('Blog', $config['name']);
        $this->assertEquals('1.0.0', $config['version']);
        $this->assertTrue($config['enabled']);
    }

    /**
     * 测试配置文件复制会覆盖已存在的文件
     *
     * Requirements: 7.3
     */
    public function testConfigFileCopyOverwritesExisting(): void
    {
        // 创建源配置文件
        $sourceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'source';
        mkdir($sourceDir, 0755, true);
        $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . 'config.php';
        file_put_contents($sourceFile, "<?php\nreturn ['version' => '2.0.0'];");

        // 创建已存在的目标文件
        $targetDir = $this->tempDir . DIRECTORY_SEPARATOR . 'target';
        mkdir($targetDir, 0755, true);
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . 'blog.php';
        file_put_contents($targetFile, "<?php\nreturn ['version' => '1.0.0'];");

        // 执行覆盖复制
        copy($sourceFile, $targetFile);

        // 验证内容已更新
        $config = include $targetFile;
        $this->assertEquals('2.0.0', $config['version']);
    }

    // ==================== 辅助方法 ====================

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

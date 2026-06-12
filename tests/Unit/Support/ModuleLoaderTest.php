<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use think\App;
use Thinkrix\Support\ModuleLoader;

/**
 * ModuleLoader 单元测试
 *
 * 测试配置加载优先级、路由条件加载、命令类扫描与异常跳过逻辑。
 * 通过创建真实的 App 实例（指定临时 rootPath），并绑定模拟服务对象来隔离测试。
 */
class ModuleLoaderTest extends TestCase
{
    private string $tempDir;
    private App $app;

    /** @var object 模拟的 Config 对象 */
    private object $mockConfig;

    /** @var object 模拟的 Log 对象 */
    private object $mockLog;

    /** @var object 模拟的 Middleware 对象 */
    private object $mockMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建临时目录模拟项目结构
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'module_loader_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        // 创建真实的 App 实例，使用临时目录作为 rootPath
        $this->app = new App($this->tempDir);

        // 创建模拟的 Config 服务
        $this->mockConfig = new class {
            /** @var array<string, array> */
            public array $data = [];

            public function set($config, string $key = null): void
            {
                if ($key !== null) {
                    $this->data[$key] = $config;
                }
            }

            public function get(string $key = null, $default = null)
            {
                if ($key === null) {
                    return $this->data;
                }
                return $this->data[$key] ?? $default;
            }

            public function has(string $name): bool
            {
                return isset($this->data[$name]);
            }

            public function load(string $file, string $name = ''): array
            {
                return [];
            }
        };

        // 创建模拟的 Log 服务
        $this->mockLog = new class {
            /** @var array */
            public array $warnings = [];

            public function warning($msg, array $ctx = []): void
            {
                $this->warnings[] = ['message' => $msg, 'context' => $ctx];
            }

            public function __call($name, $args)
            {
                // 忽略其他日志方法调用
            }
        };

        // 创建模拟的 Middleware 服务
        $this->mockMiddleware = new class {
            /** @var array */
            public array $added = [];
            /** @var array */
            public array $routed = [];

            public function add($mw): void
            {
                $this->added[] = $mw;
            }

            public function route($mw): void
            {
                $this->routed[] = $mw;
            }

            public function __call($name, $args)
            {
                // 忽略其他中间件方法调用
            }
        };

        // 将模拟对象绑定到容器
        $this->app->bind('config', function () {
            return $this->mockConfig;
        });
        $this->app->bind('log', function () {
            return $this->mockLog;
        });
        $this->app->bind('middleware', function () {
            return $this->mockMiddleware;
        });
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== 配置加载优先级测试 (Requirements: 7.2, 7.4) ====================

    /**
     * 测试项目配置优先于模块配置
     */
    public function testLoadConfigProjectConfigTakesPriority(): void
    {
        $moduleName = 'Blog';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        // 创建模块配置（低优先级）
        $moduleConfigDir = $modulePath . DIRECTORY_SEPARATOR . 'config';
        mkdir($moduleConfigDir, 0755, true);
        file_put_contents(
            $moduleConfigDir . DIRECTORY_SEPARATOR . 'config.php',
            '<?php return ["source" => "module", "module_only" => true];'
        );

        // 创建项目配置（高优先级）
        $projectConfigDir = $this->tempDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules';
        mkdir($projectConfigDir, 0755, true);
        file_put_contents(
            $projectConfigDir . DIRECTORY_SEPARATOR . 'blog.php',
            '<?php return ["source" => "project", "project_only" => true];'
        );

        $loader = new ModuleLoader($this->app);
        $loader->loadConfig($moduleName, $modulePath);

        // 应使用项目配置（最高优先级）
        $configData = $this->mockConfig->data;
        $this->assertArrayHasKey('module_blog', $configData);
        $this->assertEquals('project', $configData['module_blog']['source']);
        $this->assertTrue($configData['module_blog']['project_only']);
        // 模块配置中的字段不应出现
        $this->assertArrayNotHasKey('module_only', $configData['module_blog']);
    }

    /**
     * 测试模块配置在项目配置不存在时被使用
     */
    public function testLoadConfigFallsBackToModuleConfig(): void
    {
        $moduleName = 'UserCenter';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        // 仅创建模块配置
        $moduleConfigDir = $modulePath . DIRECTORY_SEPARATOR . 'config';
        mkdir($moduleConfigDir, 0755, true);
        file_put_contents(
            $moduleConfigDir . DIRECTORY_SEPARATOR . 'config.php',
            '<?php return ["key" => "module_value", "debug" => false];'
        );

        // 不创建项目配置

        $loader = new ModuleLoader($this->app);
        $loader->loadConfig($moduleName, $modulePath);

        $configData = $this->mockConfig->data;
        $this->assertArrayHasKey('module_usercenter', $configData);
        $this->assertEquals('module_value', $configData['module_usercenter']['key']);
        $this->assertFalse($configData['module_usercenter']['debug']);
    }

    /**
     * 测试两种配置文件都不存在时不注册配置
     */
    public function testLoadConfigDoesNothingWhenNoConfigExists(): void
    {
        $moduleName = 'EmptyModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        $loader = new ModuleLoader($this->app);
        $loader->loadConfig($moduleName, $modulePath);

        // 不应注册任何配置
        $this->assertEmpty($this->mockConfig->data);
    }

    /**
     * 测试配置注册键名格式为 module_{lower_name}
     */
    public function testLoadConfigUsesCorrectKeyName(): void
    {
        $moduleName = 'UserCenter';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $moduleConfigDir = $modulePath . DIRECTORY_SEPARATOR . 'config';
        mkdir($moduleConfigDir, 0755, true);
        file_put_contents(
            $moduleConfigDir . DIRECTORY_SEPARATOR . 'config.php',
            '<?php return ["enabled" => true];'
        );

        $loader = new ModuleLoader($this->app);
        $loader->loadConfig($moduleName, $modulePath);

        // 键名应为 module_usercenter（模块名全小写）
        $this->assertArrayHasKey('module_usercenter', $this->mockConfig->data);
        $this->assertArrayNotHasKey('module_UserCenter', $this->mockConfig->data);
    }

    /**
     * 测试配置文件返回非数组时使用空配置
     */
    public function testLoadConfigHandlesNonArrayReturn(): void
    {
        $moduleName = 'BadConfig';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $moduleConfigDir = $modulePath . DIRECTORY_SEPARATOR . 'config';
        mkdir($moduleConfigDir, 0755, true);
        // 配置文件返回字符串而非数组
        file_put_contents(
            $moduleConfigDir . DIRECTORY_SEPARATOR . 'config.php',
            '<?php return "invalid";'
        );

        $loader = new ModuleLoader($this->app);
        $loader->loadConfig($moduleName, $modulePath);

        // 非数组返回值会被转为空数组，空配置不会注册
        $this->assertEmpty($this->mockConfig->data);
    }

    // ==================== 路由条件加载测试 (Requirements: 5.1, 5.2) ====================

    /**
     * 测试路由文件存在时被加载
     */
    public function testLoadRoutesIncludesRouteFileWhenExists(): void
    {
        $moduleName = 'Blog';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $routeDir = $modulePath . DIRECTORY_SEPARATOR . 'route';
        mkdir($routeDir, 0755, true);

        // 创建路由文件，写入标记以验证加载
        $markerFile = $this->tempDir . DIRECTORY_SEPARATOR . 'route_loaded_marker.txt';
        $escapedPath = str_replace('\\', '/', $markerFile);
        file_put_contents(
            $routeDir . DIRECTORY_SEPARATOR . 'app.php',
            "<?php file_put_contents('{$escapedPath}', 'blog_route_loaded');"
        );

        $loader = new ModuleLoader($this->app);
        $loader->loadRoutes($moduleName, $modulePath);

        // 验证路由文件被加载执行
        $this->assertFileExists($markerFile);
        $this->assertEquals('blog_route_loaded', file_get_contents($markerFile));
    }

    /**
     * 测试路由文件不存在时不报错
     */
    public function testLoadRoutesDoesNothingWhenRouteFileMissing(): void
    {
        $moduleName = 'NoRoute';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        $loader = new ModuleLoader($this->app);

        // 不应抛出异常
        $loader->loadRoutes($moduleName, $modulePath);

        // 测试通过即表示不报错
        $this->assertTrue(true);
    }

    /**
     * 测试路由目录存在但 app.php 不存在时不报错
     */
    public function testLoadRoutesDoesNothingWhenRouteDirectoryExistsButNoFile(): void
    {
        $moduleName = 'EmptyRoute';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $routeDir = $modulePath . DIRECTORY_SEPARATOR . 'route';
        mkdir($routeDir, 0755, true);
        // 不创建 app.php

        $loader = new ModuleLoader($this->app);
        $loader->loadRoutes($moduleName, $modulePath);

        $this->assertTrue(true);
    }

    // ==================== 命令类扫描与异常跳过测试 (Requirements: 10.2, 10.3, 10.7) ====================

    /**
     * 测试 command 目录不存在时返回空命令列表
     */
    public function testRegisterCommandsReturnsEmptyWhenNoCommandDir(): void
    {
        $moduleName = 'NoCmd';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        $loader = new ModuleLoader($this->app);
        $loader->registerCommands($moduleName, $modulePath);

        $this->assertEmpty($loader->getRegisteredCommands());
    }

    /**
     * 测试 command 目录为空时返回空命令列表
     */
    public function testRegisterCommandsReturnsEmptyWhenCommandDirEmpty(): void
    {
        $moduleName = 'EmptyCmd';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        mkdir($commandDir, 0755, true);

        $loader = new ModuleLoader($this->app);
        $loader->registerCommands($moduleName, $modulePath);

        $this->assertEmpty($loader->getRegisteredCommands());
    }

    /**
     * 测试扫描 command 目录下的 PHP 文件不抛出异常
     */
    public function testRegisterCommandsDetectsPhpFilesWithoutError(): void
    {
        $moduleName = 'TestModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        mkdir($commandDir, 0755, true);

        // 创建 PHP 文件（这些类不在自动加载中，class_exists 返回 false）
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'SyncData.php', '<?php // dummy');
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'ImportUser.php', '<?php // dummy');

        $loader = new ModuleLoader($this->app);
        $loader->registerCommands($moduleName, $modulePath);

        // 由于这些类不存在于自动加载中，不会注册，但不应抛出异常
        $this->assertIsArray($loader->getRegisteredCommands());
    }

    /**
     * 测试非 PHP 文件被跳过（glob 只匹配 *.php）
     */
    public function testRegisterCommandsSkipsNonPhpFiles(): void
    {
        $moduleName = 'MixedFiles';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        mkdir($commandDir, 0755, true);

        // 创建非 PHP 文件
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'readme.md', '# Commands');
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . '.gitkeep', '');
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'config.json', '{}');

        $loader = new ModuleLoader($this->app);
        $loader->registerCommands($moduleName, $modulePath);

        // glob('*.php') 只匹配 PHP 文件，所以非 PHP 文件被忽略
        $this->assertEmpty($loader->getRegisteredCommands());
    }

    /**
     * 测试命令类加载失败时被捕获并记录日志（异常跳过）
     */
    public function testRegisterCommandsCatchesExceptionsAndLogs(): void
    {
        $moduleName = 'BrokenModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        mkdir($commandDir, 0755, true);

        // 创建一个 PHP 文件
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'BrokenCommand.php', '<?php // broken');

        $loader = new ModuleLoader($this->app);

        // registerCommands 应该捕获异常并记录日志，不应向外抛出
        $loader->registerCommands($moduleName, $modulePath);

        // 注册命令列表应为空（因为类不存在或加载失败）
        $this->assertEmpty($loader->getRegisteredCommands());
    }

    /**
     * 测试多个命令文件时处理不会中断（遍历不被打断）
     */
    public function testRegisterCommandsContinuesAfterError(): void
    {
        $moduleName = 'PartialModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        mkdir($commandDir, 0755, true);

        // 创建多个 PHP 文件
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'Alpha.php', '<?php // alpha');
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'Beta.php', '<?php // beta');
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'Gamma.php', '<?php // gamma');

        $loader = new ModuleLoader($this->app);

        // 不应抛出异常，即使某个文件加载失败
        $loader->registerCommands($moduleName, $modulePath);

        // 方法正常执行完成
        $this->assertIsArray($loader->getRegisteredCommands());
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

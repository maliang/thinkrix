<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * ModuleGenerator 单元测试
 */
class ModuleGeneratorTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;
    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建临时目录模拟项目结构
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'module_generator_test_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'app', 0755, true);

        // 创建基础 stub 模板
        $this->createStubFiles();

        // 使用可测试的 StubResolver
        $stubResolver = $this->createStubResolver();

        // 使用可测试的 ModuleGenerator（覆盖 getModulePath 使用临时目录）
        $this->generator = $this->createGenerator($stubResolver);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== studlyCase 测试 ====================

    /**
     * 测试连字符分隔的名称转换
     */
    public function testStudlyCaseConvertsHyphenSeparated(): void
    {
        $this->assertEquals('UserCenter', $this->generator->studlyCase('user-center'));
    }

    /**
     * 测试下划线分隔的名称转换
     */
    public function testStudlyCaseConvertsUnderscoreSeparated(): void
    {
        $this->assertEquals('UserCenter', $this->generator->studlyCase('user_center'));
    }

    /**
     * 测试空格分隔的名称转换
     */
    public function testStudlyCaseConvertsSpaceSeparated(): void
    {
        $this->assertEquals('UserCenter', $this->generator->studlyCase('user center'));
    }

    /**
     * 测试混合分隔符的名称转换
     */
    public function testStudlyCaseConvertsMixedSeparators(): void
    {
        $this->assertEquals('UserCenterAdmin', $this->generator->studlyCase('user-center_admin'));
    }

    /**
     * 测试已经是 StudlyCase 的名称保持不变
     */
    public function testStudlyCasePreservesAlreadyStudly(): void
    {
        $this->assertEquals('UserCenter', $this->generator->studlyCase('UserCenter'));
    }

    /**
     * 测试全大写输入
     */
    public function testStudlyCaseHandlesUpperCase(): void
    {
        $this->assertEquals('User', $this->generator->studlyCase('USER'));
    }

    /**
     * 测试单个单词
     */
    public function testStudlyCaseHandlesSingleWord(): void
    {
        $this->assertEquals('Blog', $this->generator->studlyCase('blog'));
    }

    /**
     * 测试包含数字的名称
     */
    public function testStudlyCaseHandlesNumbers(): void
    {
        $this->assertEquals('Module2Test', $this->generator->studlyCase('module2-test'));
    }

    /**
     * 测试输出以大写字母开头
     */
    public function testStudlyCaseOutputStartsWithUppercase(): void
    {
        $result = $this->generator->studlyCase('my-module');
        $this->assertMatchesRegularExpression('/^[A-Z]/', $result);
    }

    /**
     * 测试输出仅包含字母和数字
     */
    public function testStudlyCaseOutputOnlyAlphanumeric(): void
    {
        $result = $this->generator->studlyCase('user-center_admin test');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $result);
    }

    // ==================== moduleExists 测试 ====================

    /**
     * 测试模块存在返回 true
     */
    public function testModuleExistsReturnsTrueWhenExists(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        mkdir($modulePath, 0755, true);

        $this->assertTrue($this->generator->moduleExists('UserCenter'));
    }

    /**
     * 测试模块不存在返回 false
     */
    public function testModuleExistsReturnsFalseWhenNotExists(): void
    {
        $this->assertFalse($this->generator->moduleExists('NonExistent'));
    }

    // ==================== getModulePath 测试 ====================

    /**
     * 测试获取模块路径格式正确
     */
    public function testGetModulePathReturnsCorrectPath(): void
    {
        $path = $this->generator->getModulePath('UserCenter');
        $expected = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        $this->assertEquals($expected, $path);
    }

    // ==================== createModule 测试 ====================

    /**
     * 测试标准模式创建模块目录结构
     */
    public function testCreateModuleCreatesStandardDirectories(): void
    {
        $result = $this->generator->createModule('user-center');

        $this->assertTrue($result);

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'controller');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'model');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'service');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'validate');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'middleware');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'event');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'listener');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'command');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'config');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'route');
    }

    /**
     * 测试标准模式生成 module.json
     */
    public function testCreateModuleGeneratesModuleJson(): void
    {
        $this->generator->createModule('blog');

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');

        $content = file_get_contents($modulePath . DIRECTORY_SEPARATOR . 'module.json');
        $this->assertStringContainsString('Blog', $content);
    }

    /**
     * 测试标准模式生成示例文件
     */
    public function testCreateModuleGeneratesSampleFiles(): void
    {
        $this->generator->createModule('blog');

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php');
    }

    /**
     * 测试 plain 模式不生成示例文件
     */
    public function testCreateModulePlainModeOnlyCreatesDirectories(): void
    {
        $this->generator->createModule('blog', ['plain' => true]);

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';

        // 目录应该存在
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'controller');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'model');

        // module.json 始终生成
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');

        // 示例文件不应存在
        $configFile = $modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $routeFile = $modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php';
        $controllerFile = $modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php';
        $this->assertFileDoesNotExist($configFile);
        $this->assertFileDoesNotExist($routeFile);
        $this->assertFileDoesNotExist($controllerFile);
    }

    /**
     * 测试同名模块已存在时返回 false
     */
    public function testCreateModuleReturnsFalseWhenAlreadyExists(): void
    {
        // 先手动创建目录
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        mkdir($modulePath, 0755, true);

        $result = $this->generator->createModule('blog');

        $this->assertFalse($result);
    }

    /**
     * 测试名称自动转换为 StudlyCase
     */
    public function testCreateModuleConvertsNameToStudlyCase(): void
    {
        $this->generator->createModule('user-center');

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        $this->assertDirectoryExists($modulePath);
    }

    // ==================== generateResource 测试 ====================

    /**
     * 测试生成控制器资源
     */
    public function testGenerateResourceController(): void
    {
        // 先创建模块
        $this->generator->createModule('blog');

        $filePath = $this->generator->generateResource('blog', 'controller', 'User');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('controller', $filePath);
        $this->assertStringContainsString('User.php', $filePath);

        // 检查命名空间
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('app\\Blog\\controller', $content);
        $this->assertStringContainsString('User', $content);
    }

    /**
     * 测试生成模型资源
     */
    public function testGenerateResourceModel(): void
    {
        $this->generator->createModule('blog');

        $filePath = $this->generator->generateResource('blog', 'model', 'Post');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('model', $filePath);
        $this->assertStringContainsString('Post.php', $filePath);
    }

    /**
     * 测试生成迁移资源（带时间戳前缀）
     */
    public function testGenerateResourceMigrationWithTimestamp(): void
    {
        $this->generator->createModule('blog');

        $filePath = $this->generator->generateResource('blog', 'migration', 'create_posts');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        // 迁移文件名应包含时间戳和表名
        $filename = basename($filePath);
        $this->assertMatchesRegularExpression('/^\d{14}_create_create_posts_table\.php$/', $filename);
    }

    /**
     * 测试目标模块不存在时返回空字符串
     */
    public function testGenerateResourceReturnsEmptyWhenModuleNotExists(): void
    {
        $filePath = $this->generator->generateResource('NonExistent', 'controller', 'Test');

        $this->assertEmpty($filePath);
    }

    /**
     * 测试无效资源类型返回空字符串
     */
    public function testGenerateResourceReturnsEmptyForInvalidType(): void
    {
        $this->generator->createModule('blog');

        $filePath = $this->generator->generateResource('blog', 'invalid_type', 'Test');

        $this->assertEmpty($filePath);
    }

    /**
     * 测试生成命令资源
     */
    public function testGenerateResourceCommand(): void
    {
        $this->generator->createModule('blog');

        $filePath = $this->generator->generateResource('blog', 'command', 'sync-data');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('app\\Blog\\command', $content);
        $this->assertStringContainsString('SyncData', $content);
    }

    // ==================== 辅助方法 ====================

    /**
     * 创建基础 Stub 文件
     */
    private function createStubFiles(): void
    {
        // module.json.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'module.json.stub',
            '{"name": "{{MODULE_NAME}}", "alias": "{{LOWER_NAME}}", "enabled": true}'
        );

        // config.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'config.stub',
            "<?php\n// {{MODULE_NAME}} config\nreturn [];\n"
        );

        // route.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'route.stub',
            "<?php\nuse think\\facade\\Route;\nRoute::group('{{LOWER_NAME}}', function () {});\n"
        );

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
            "<?php\nnamespace {{NAMESPACE}};\nuse think\\Model;\nclass {{CLASS_NAME}} extends Model {\n    protected \$table = '{{TABLE_NAME}}';\n}\n"
        );

        // service.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'service.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // migration.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'migration.stub',
            "<?php\nuse think\\migration\\Migrator;\nclass {{CLASS_NAME}} extends Migrator {\n    public function up(): void { \$this->table('{{TABLE_NAME}}')->create(); }\n}\n"
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
            "<?php\nnamespace {{NAMESPACE}};\nuse think\\console\\Command;\nclass {{CLASS_NAME}} extends Command {\n    protected function configure(): void { \$this->setName('{{LOWER_NAME}}:{{TABLE_NAME}}'); }\n}\n"
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

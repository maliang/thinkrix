<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Thinkrix\Support\StubResolver;

/**
 * StubResolver 单元测试
 */
class StubResolverTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建临时目录模拟项目结构
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'stub_resolver_test_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'package' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        // 自定义目录不一定存在
    }

    protected function tearDown(): void
    {
        // 递归删除临时目录
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * 测试 resolve 方法替换所有占位符
     */
    public function testResolveReplacesAllPlaceholders(): void
    {
        // 创建测试 Stub 文件
        $stubContent = "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {\n    // Module: {{MODULE_NAME}}\n}";
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'test.stub', $stubContent);

        $resolver = $this->createResolver();

        $result = $resolver->resolve('test.stub', [
            '{{NAMESPACE}}' => 'app\\UserCenter\\controller',
            '{{CLASS_NAME}}' => 'UserController',
            '{{MODULE_NAME}}' => 'UserCenter',
        ]);

        $this->assertStringContainsString('namespace app\\UserCenter\\controller;', $result);
        $this->assertStringContainsString('class UserController', $result);
        $this->assertStringContainsString('Module: UserCenter', $result);
        $this->assertStringNotContainsString('{{NAMESPACE}}', $result);
        $this->assertStringNotContainsString('{{CLASS_NAME}}', $result);
        $this->assertStringNotContainsString('{{MODULE_NAME}}', $result);
    }

    /**
     * 测试 resolve 方法支持所有标准占位符
     */
    public function testResolveSupportsAllStandardPlaceholders(): void
    {
        $stubContent = "{{MODULE_NAME}} {{LOWER_NAME}} {{NAMESPACE}} {{CLASS_NAME}} {{TABLE_NAME}} {{TIMESTAMP}}";
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'full.stub', $stubContent);

        $resolver = $this->createResolver();

        $result = $resolver->resolve('full.stub', [
            '{{MODULE_NAME}}' => 'UserCenter',
            '{{LOWER_NAME}}' => 'usercenter',
            '{{NAMESPACE}}' => 'app\\UserCenter\\model',
            '{{CLASS_NAME}}' => 'User',
            '{{TABLE_NAME}}' => 'user_center_users',
            '{{TIMESTAMP}}' => '20240101120000',
        ]);

        $this->assertEquals('UserCenter usercenter app\\UserCenter\\model User user_center_users 20240101120000', $result);
    }

    /**
     * 测试 getStubPath 优先返回自定义路径
     */
    public function testGetStubPathPrefersCustomPath(): void
    {
        // 创建两个同名文件
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub', 'default');
        mkdir($this->customStubDir, 0755, true);
        file_put_contents($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub', 'custom');

        $resolver = $this->createResolver();

        $path = $resolver->getStubPath('controller.stub');

        $this->assertEquals($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub', $path);
    }

    /**
     * 测试 getStubPath 自定义不存在时回退默认
     */
    public function testGetStubPathFallsBackToDefault(): void
    {
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'model.stub', 'default model');

        $resolver = $this->createResolver();

        $path = $resolver->getStubPath('model.stub');

        $this->assertEquals($this->packageStubDir . DIRECTORY_SEPARATOR . 'model.stub', $path);
    }

    /**
     * 测试 resolve 当文件不存在时返回空字符串并触发警告
     */
    public function testResolveReturnsEmptyStringWhenStubMissing(): void
    {
        $resolver = $this->createResolver();

        // 期望触发 E_USER_WARNING
        $warningTriggered = false;
        set_error_handler(function ($errno) use (&$warningTriggered) {
            if ($errno === E_USER_WARNING) {
                $warningTriggered = true;
            }
            return true;
        });

        $result = $resolver->resolve('nonexistent.stub', ['{{MODULE_NAME}}' => 'Test']);

        restore_error_handler();

        $this->assertEmpty($result);
        $this->assertTrue($warningTriggered, '应当触发 E_USER_WARNING');
    }

    /**
     * 测试 publishStubs 方法将文件复制到目标目录
     */
    public function testPublishStubsCopiesFiles(): void
    {
        // 创建几个默认 Stub 文件
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub', 'controller content');
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'model.stub', 'model content');

        $resolver = $this->createResolver();
        $published = $resolver->publishStubs();

        $this->assertCount(2, $published);
        $this->assertArrayHasKey('controller.stub', $published);
        $this->assertArrayHasKey('model.stub', $published);

        // 验证文件确实被复制
        $this->assertFileExists($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub');
        $this->assertFileExists($this->customStubDir . DIRECTORY_SEPARATOR . 'model.stub');
        $this->assertEquals('controller content', file_get_contents($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub'));
    }

    /**
     * 测试 publishStubs 不覆盖已存在的文件
     */
    public function testPublishStubsDoesNotOverwriteExisting(): void
    {
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub', 'default');
        mkdir($this->customStubDir, 0755, true);
        file_put_contents($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub', 'customized');

        $resolver = $this->createResolver();
        $resolver->publishStubs();

        // 已存在的文件不应被覆盖
        $this->assertEquals('customized', file_get_contents($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub'));
    }

    /**
     * 测试 getPlaceholders 返回所有支持的占位符
     */
    public function testGetPlaceholdersReturnsExpectedKeys(): void
    {
        $resolver = $this->createResolver();
        $placeholders = $resolver->getPlaceholders();

        $this->assertArrayHasKey('{{MODULE_NAME}}', $placeholders);
        $this->assertArrayHasKey('{{LOWER_NAME}}', $placeholders);
        $this->assertArrayHasKey('{{NAMESPACE}}', $placeholders);
        $this->assertArrayHasKey('{{CLASS_NAME}}', $placeholders);
        $this->assertArrayHasKey('{{TABLE_NAME}}', $placeholders);
        $this->assertArrayHasKey('{{TIMESTAMP}}', $placeholders);
    }

    /**
     * 创建可测试的 StubResolver 实例（注入测试目录路径）
     */
    private function createResolver(): StubResolver
    {
        $resolver = new class($this->packageStubDir, $this->customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };

        return $resolver;
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

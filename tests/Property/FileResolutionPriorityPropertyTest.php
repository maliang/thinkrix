<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Property;

use Eris\TestTrait;
use Eris\Generators;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\StubResolver;

/**
 * StubResolver 文件解析优先级属性测试
 *
 * // Feature: laravel-modules, Property 3: 文件解析优先级——自定义文件存在时优先返回
 *
 * **Validates: Requirements 6.2, 6.3**
 *
 * 对任意文件名标识符，当自定义目录中存在同名文件时，
 * 解析器应返回自定义路径；当自定义文件不存在但默认文件存在时，应返回默认路径。
 */
class FileResolutionPriorityPropertyTest extends TestCase
{
    use TestTrait;

    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pbt_file_resolution_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'package' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        mkdir($this->customStubDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * Property 3a: 当自定义目录中存在同名文件时，getStubPath 返回自定义路径
     *
     * // Feature: laravel-modules, Property 3: 文件解析优先级——自定义文件存在时优先返回
     */
    public function testCustomFileAlwaysTakesPriority(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::filter(
                fn($name) => preg_match('/^[a-zA-Z0-9_\-]+$/', $name) === 1 && strlen($name) > 0,
                Generators::string()
            )
        )->then(function (string $fileName): void {
            $stubName = $fileName . '.stub';

            // 在两个目录中都创建文件
            file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . $stubName, 'default content');
            file_put_contents($this->customStubDir . DIRECTORY_SEPARATOR . $stubName, 'custom content');

            $resolver = $this->createResolver();
            $resolvedPath = $resolver->getStubPath($stubName);

            // 当自定义文件存在时，应优先返回自定义路径
            $this->assertEquals(
                $this->customStubDir . DIRECTORY_SEPARATOR . $stubName,
                $resolvedPath,
                "当自定义目录存在文件 [{$stubName}] 时，应优先返回自定义路径"
            );

            // 清理此次迭代创建的文件
            @unlink($this->packageStubDir . DIRECTORY_SEPARATOR . $stubName);
            @unlink($this->customStubDir . DIRECTORY_SEPARATOR . $stubName);
        });
    }

    /**
     * Property 3b: 当自定义文件不存在但默认文件存在时，getStubPath 返回默认路径
     *
     * // Feature: laravel-modules, Property 3: 文件解析优先级——自定义文件存在时优先返回
     */
    public function testFallsBackToDefaultWhenCustomNotExists(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::filter(
                fn($name) => preg_match('/^[a-zA-Z0-9_\-]+$/', $name) === 1 && strlen($name) > 0,
                Generators::string()
            )
        )->then(function (string $fileName): void {
            $stubName = $fileName . '.stub';

            // 仅在默认目录中创建文件，不在自定义目录中创建
            file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . $stubName, 'default content');

            // 确保自定义目录中不存在该文件
            $customFile = $this->customStubDir . DIRECTORY_SEPARATOR . $stubName;
            if (file_exists($customFile)) {
                unlink($customFile);
            }

            $resolver = $this->createResolver();
            $resolvedPath = $resolver->getStubPath($stubName);

            // 自定义不存在时应回退到默认路径
            $this->assertEquals(
                $this->packageStubDir . DIRECTORY_SEPARATOR . $stubName,
                $resolvedPath,
                "当自定义目录不存在文件 [{$stubName}] 时，应回退返回默认路径"
            );

            // 清理
            @unlink($this->packageStubDir . DIRECTORY_SEPARATOR . $stubName);
        });
    }

    /**
     * 创建可测试的 StubResolver 实例（注入测试目录路径）
     */
    private function createResolver(): StubResolver
    {
        $packageStubDir = $this->packageStubDir;
        $customStubDir = $this->customStubDir;

        return new class($packageStubDir, $customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
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

<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Property;

use Eris\TestTrait;
use Eris\Generators;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\StubResolver;

/**
 * StubResolver 占位符替换完整性属性测试
 *
 * // Feature: laravel-modules, Property 4: Stub 占位符替换完整性
 *
 * **Validates: Requirements 6.3**
 *
 * 对任意包含已知占位符的模板字符串和对应的替换映射，
 * resolve() 的输出中不应再包含任何映射中定义的占位符标记，
 * 且输出应包含所有替换值。
 */
class StubReplacementPropertyTest extends TestCase
{
    use TestTrait;

    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pbt_stub_replacement_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'package' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        // 自定义目录不需要创建，用于回退测试
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * Property 4: resolve() 输出不包含任何占位符标记，且包含所有替换值
     *
     * // Feature: laravel-modules, Property 4: Stub 占位符替换完整性
     */
    public function testAllPlaceholdersAreReplacedAndValuesPresent(): void
    {
        $this->limitTo(100);

        // 生成随机的替换值（字母数字字符串，保证非空且不含占位符语法）
        $valueGenerator = Generators::filter(
            fn($v) => strlen($v) > 0 && !str_contains($v, '{{') && !str_contains($v, '}}'),
            Generators::string()
        );

        $this->forAll(
            $valueGenerator, // MODULE_NAME 替换值
            $valueGenerator, // LOWER_NAME 替换值
            $valueGenerator, // NAMESPACE 替换值
            $valueGenerator, // CLASS_NAME 替换值
            $valueGenerator, // TABLE_NAME 替换值
            $valueGenerator  // TIMESTAMP 替换值
        )->then(function (
            string $moduleName,
            string $lowerName,
            string $namespace,
            string $className,
            string $tableName,
            string $timestamp
        ): void {
            // 定义占位符和替换映射
            $replacements = [
                '{{MODULE_NAME}}' => $moduleName,
                '{{LOWER_NAME}}'  => $lowerName,
                '{{NAMESPACE}}'   => $namespace,
                '{{CLASS_NAME}}'  => $className,
                '{{TABLE_NAME}}'  => $tableName,
                '{{TIMESTAMP}}'   => $timestamp,
            ];

            // 构建包含所有占位符的模板内容
            $templateContent = "namespace {{NAMESPACE}};\n"
                . "class {{CLASS_NAME}} {\n"
                . "    // Module: {{MODULE_NAME}}\n"
                . "    // Lower: {{LOWER_NAME}}\n"
                . "    // Table: {{TABLE_NAME}}\n"
                . "    // Time: {{TIMESTAMP}}\n"
                . "}\n";

            // 将模板写入测试 Stub 文件
            $stubName = 'pbt_test_' . uniqid() . '.stub';
            file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . $stubName, $templateContent);

            $resolver = $this->createResolver();
            $result = $resolver->resolve($stubName, $replacements);

            // 断言：输出不应包含任何定义的占位符标记
            foreach (array_keys($replacements) as $placeholder) {
                $this->assertStringNotContainsString(
                    $placeholder,
                    $result,
                    "输出中不应包含占位符 [{$placeholder}]"
                );
            }

            // 断言：输出应包含所有替换值
            foreach ($replacements as $placeholder => $value) {
                $this->assertStringContainsString(
                    $value,
                    $result,
                    "输出中应包含替换值 [{$value}]（对应占位符 {$placeholder}）"
                );
            }

            // 清理此次迭代创建的 Stub 文件
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

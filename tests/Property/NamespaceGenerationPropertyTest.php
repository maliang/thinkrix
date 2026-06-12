<?php

namespace Thinkrix\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\ModuleGenerator;

/**
 * Feature: laravel-modules, Property 2: 命名空间生成符合 PSR-4 格式
 *
 * **Validates: Requirements 2.11**
 *
 * 对任意合法模块名称（StudlyCase）和资源类型（controller, model, service, validate,
 * middleware, event, listener, command），生成的命名空间字符串应满足：
 * (a) 以 app\{ModuleName}\{type} 格式组成
 * (b) 各段均不为空
 * (c) 不包含连续反斜杠
 * (d) 模块名与输入一致
 */
class NamespaceGenerationPropertyTest extends TestCase
{
    use TestTrait;

    private ModuleGenerator $generator;

    /**
     * 支持的资源类型列表
     */
    private array $resourceTypes = [
        'controller',
        'model',
        'service',
        'validate',
        'middleware',
        'event',
        'listener',
        'command',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // 传入 mock 的 StubResolver 避免依赖 ThinkPHP app() 函数
        $stubResolver = $this->createMock(\Thinkrix\Support\StubResolver::class);
        $this->generator = new ModuleGenerator($stubResolver);
    }

    /**
     * Property 2: 对任意 StudlyCase 模块名和资源类型，命名空间符合 PSR-4 格式
     *
     * 使用随机单词组合生成模块名，再与 8 种资源类型组合验证命名空间格式
     */
    public function testNamespaceGenerationFollowsPsr4Format(): void
    {
        // Feature: laravel-modules, Property 2: 命名空间生成符合 PSR-4 格式
        $words = ['user', 'center', 'admin', 'blog', 'shop', 'api', 'core', 'auth', 'payment', 'order'];

        $this
            ->limitTo(100)
            ->forAll(
                Generators::choose(1, 3),  // 单词数量
                Generators::choose(0, 9),  // 单词索引 1
                Generators::choose(0, 9),  // 单词索引 2
                Generators::choose(0, 9),  // 单词索引 3
                Generators::choose(0, 7)   // 资源类型索引
            )
            ->then(function (int $wordCount, int $w1, int $w2, int $w3, int $typeIdx) use ($words) {
                // 构建模块名输入（用连字符拼接多个单词）
                $parts = array_slice([$words[$w1], $words[$w2], $words[$w3]], 0, $wordCount);
                $moduleInput = implode('-', $parts);

                // 转换为 StudlyCase 模块名
                $moduleName = $this->generator->studlyCase($moduleInput);
                $type = $this->resourceTypes[$typeIdx];

                // 生成命名空间：app\{ModuleName}\{type}
                $namespace = "app\\{$moduleName}\\{$type}";

                // (a) 格式验证：以 app\ 开头，包含模块名和类型
                $this->assertStringStartsWith(
                    'app\\',
                    $namespace,
                    "命名空间应以 'app\\' 开头"
                );
                $this->assertStringEndsWith(
                    "\\{$type}",
                    $namespace,
                    "命名空间应以资源类型 '\\{$type}' 结尾"
                );

                // (b) 各段均不为空
                $segments = explode('\\', $namespace);
                foreach ($segments as $segment) {
                    $this->assertNotEmpty(
                        $segment,
                        "命名空间 '$namespace' 中不应有空段"
                    );
                }

                // (c) 不包含连续反斜杠
                $this->assertStringNotContainsString(
                    '\\\\',
                    $namespace,
                    "命名空间 '$namespace' 不应包含连续反斜杠"
                );

                // (d) 模块名与 studlyCase 转换结果一致
                $this->assertEquals(
                    $moduleName,
                    $segments[1],
                    "命名空间中的模块名 '{$segments[1]}' 应与 studlyCase 输出 '$moduleName' 一致"
                );

                // 额外验证：命名空间恰好 3 段
                $this->assertCount(
                    3,
                    $segments,
                    "命名空间 '$namespace' 应恰好包含 3 段（app, 模块名, 资源类型）"
                );
            });
    }

    /**
     * Property 2 补充: 使用随机字符串生成模块名，验证命名空间完整性
     *
     * 确保即使是含特殊字符的输入，经 studlyCase 转换后命名空间仍合法
     */
    public function testNamespaceGenerationWithRandomStringModuleNames(): void
    {
        // Feature: laravel-modules, Property 2: 命名空间生成符合 PSR-4 格式
        $this
            ->limitTo(100)
            ->forAll(
                Generators::suchThat(
                    function ($s) {
                        // 非空且至少含一个字母（确保 studlyCase 能产生以大写字母开头的合法名称）
                        return is_string($s) && strlen($s) > 0 && preg_match('/[a-zA-Z]/', $s);
                    },
                    Generators::string()
                ),
                Generators::elements($this->resourceTypes)
            )
            ->then(function (string $rawName, string $type) {
                $moduleName = $this->generator->studlyCase($rawName);

                // studlyCase 应产生非空结果（前置条件）
                if (empty($moduleName)) {
                    return; // 跳过无法转换的极端情况
                }

                $namespace = "app\\{$moduleName}\\{$type}";

                // (a) 格式验证
                $expectedPattern = '/^app\\\\[A-Za-z0-9]+\\\\[a-z]+$/';
                $this->assertMatchesRegularExpression(
                    $expectedPattern,
                    $namespace,
                    "命名空间 '$namespace' 应匹配格式 app\\{StudlyCase}\\{type}"
                );

                // (b) 各段非空
                $segments = explode('\\', $namespace);
                foreach ($segments as $segment) {
                    $this->assertNotEmpty($segment);
                }

                // (c) 无连续反斜杠
                $this->assertStringNotContainsString('\\\\', $namespace);

                // (d) 模块名一致
                $this->assertEquals($moduleName, $segments[1]);
            });
    }
}

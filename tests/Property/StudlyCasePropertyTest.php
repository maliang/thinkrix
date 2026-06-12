<?php

namespace Thinkrix\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\ModuleGenerator;

/**
 * Feature: laravel-modules, Property 1: StudlyCase 转换始终产生合法目录名
 *
 * **Validates: Requirements 1.3**
 *
 * 对任意非空字符串输入，ModuleGenerator::studlyCase() 的输出应始终：
 * (a) 以大写字母开头
 * (b) 仅包含字母和数字（无空格、下划线、特殊字符）
 * (c) 长度大于 0
 */
class StudlyCasePropertyTest extends TestCase
{
    use TestTrait;

    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        // 传入 mock 的 StubResolver 避免依赖 ThinkPHP app() 函数
        $stubResolver = $this->createMock(\Thinkrix\Support\StubResolver::class);
        $this->generator = new ModuleGenerator($stubResolver);
    }

    /**
     * Property 1: 对随机字符串输入，studlyCase 输出始终是合法目录名
     *
     * 使用 Eris 的 string() 生成器，过滤非空且至少含一个字母或数字的字符串
     */
    public function testStudlyCaseAlwaysProducesValidDirectoryName(): void
    {
        // Feature: laravel-modules, Property 1: StudlyCase 转换始终产生合法目录名
        $this
            ->limitTo(100)
            ->forAll(
                Generators::suchThat(
                    function ($s) {
                        // 非空字符串且至少包含一个字母（确保 studlyCase 能产生以大写开头的结果）
                        return is_string($s) && strlen($s) > 0 && preg_match('/[a-zA-Z]/', $s);
                    },
                    Generators::string()
                )
            )
            ->then(function (string $input) {
                $result = $this->generator->studlyCase($input);

                // (c) 长度大于 0
                $this->assertNotEmpty(
                    $result,
                    "studlyCase('$input') 应产生非空输出"
                );

                // (a) 以大写字母开头
                $this->assertMatchesRegularExpression(
                    '/^[A-Z]/',
                    $result,
                    "studlyCase('$input') = '$result' 应以大写字母开头"
                );

                // (b) 仅包含字母和数字
                $this->assertMatchesRegularExpression(
                    '/^[A-Za-z0-9]+$/',
                    $result,
                    "studlyCase('$input') = '$result' 应仅包含字母和数字"
                );
            });
    }

    /**
     * Property 1 补充: 使用含空格、下划线、连字符、数字的自定义生成器
     *
     * 确保典型模块名称输入（如 user-center、my_module 等）能正确转换
     */
    public function testStudlyCaseWithTypicalModuleNameInputs(): void
    {
        // Feature: laravel-modules, Property 1: StudlyCase 转换始终产生合法目录名
        $separators = [' ', '_', '-'];
        $words = ['user', 'center', 'my', 'module', 'admin', 'api', 'test', 'app', 'core', 'data'];

        $this
            ->limitTo(100)
            ->forAll(
                Generators::choose(1, 4),  // 单词数量 1-4
                Generators::choose(0, 2),  // 分隔符索引
                Generators::choose(0, 9),  // 第一个单词索引
                Generators::choose(0, 9),  // 第二个单词索引
                Generators::choose(0, 9),  // 第三个单词索引
                Generators::choose(0, 9)   // 第四个单词索引
            )
            ->then(function (int $wordCount, int $sepIdx, int $w1, int $w2, int $w3, int $w4) use ($separators, $words) {
                $sep = $separators[$sepIdx];
                $parts = array_slice([$words[$w1], $words[$w2], $words[$w3], $words[$w4]], 0, $wordCount);
                $input = implode($sep, $parts);

                $result = $this->generator->studlyCase($input);

                // (c) 长度大于 0
                $this->assertNotEmpty($result);

                // (a) 以大写字母开头
                $this->assertMatchesRegularExpression(
                    '/^[A-Z]/',
                    $result,
                    "studlyCase('$input') = '$result' 应以大写字母开头"
                );

                // (b) 仅包含字母和数字
                $this->assertMatchesRegularExpression(
                    '/^[A-Za-z0-9]+$/',
                    $result,
                    "studlyCase('$input') = '$result' 应仅包含字母和数字"
                );
            });
    }

    public function testStudlyCasePreservesCamelCaseWordBoundaries(): void
    {
        $this->assertSame('UserCenter', $this->generator->studlyCase('userCenter'));
        $this->assertSame('Order2Detail', $this->generator->studlyCase('order2Detail'));
    }
}

<?php

namespace Thinkrix\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\ModuleGenerator;

/**
 * Feature: laravel-modules, Property 5: 命令命名格式一致性
 *
 * **Validates: Requirements 10.5**
 *
 * 对任意合法的模块名称和命令名称，生成的命令标识符应严格遵循
 * `{module_lower_name}:{command_lower_name}` 格式，其中：
 * - module_lower_name = strtolower(studlyCase(module_input))，纯小写字母/数字
 * - command_lower_name = toSnakeCase(command_input)，纯小写字母/数字/下划线
 * - 两部分以冒号分隔，整个标识符中恰好有一个冒号
 */
class CommandNamingPropertyTest extends TestCase
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
     * 模拟 ModuleGenerator::toSnakeCase() 行为
     *
     * 该方法为 protected，此处复制其逻辑以在测试中验证命名格式。
     * toSnakeCase 的实现：先 studlyCase，然后在大写字母前插入下划线，最后转小写。
     */
    private function computeSnakeCase(string $name): string
    {
        $studly = $this->generator->studlyCase($name);

        // 在大写字母前插入下划线（首字母除外）
        $snake = preg_replace('/([a-z\d])([A-Z])/', '$1_$2', $studly);

        return strtolower($snake);
    }

    /**
     * 构建命令标识符，模拟 command.stub 中的 {{LOWER_NAME}}:{{TABLE_NAME}} 格式
     *
     * @param string $moduleInput 模块名称输入
     * @param string $commandInput 命令名称输入
     * @return string 命令标识符
     */
    private function buildCommandIdentifier(string $moduleInput, string $commandInput): string
    {
        // LOWER_NAME = strtolower(studlyCase(module_input))
        $moduleLower = strtolower($this->generator->studlyCase($moduleInput));

        // TABLE_NAME = toSnakeCase(command_input)
        $commandSnake = $this->computeSnakeCase($commandInput);

        return "{$moduleLower}:{$commandSnake}";
    }

    /**
     * Property 5: 使用典型模块名和命令名组合，验证命令标识符格式一致性
     *
     * 使用预定义单词列表与随机分隔符组合生成模块名和命令名，
     * 验证生成的命令标识符严格遵循 {module_lower}:{command_snake} 格式。
     */
    public function testCommandNamingFormatWithTypicalInputs(): void
    {
        // Feature: laravel-modules, Property 5: 命令命名格式一致性
        $moduleWords = ['user', 'center', 'admin', 'blog', 'shop', 'api', 'core', 'auth', 'payment', 'order'];
        $commandWords = ['sync', 'data', 'clear', 'cache', 'import', 'export', 'generate', 'report', 'send', 'notify'];
        $separators = ['-', '_', ' '];

        $this
            ->limitTo(100)
            ->forAll(
                Generators::choose(1, 3),  // 模块单词数量
                Generators::choose(0, 2),  // 模块分隔符索引
                Generators::choose(0, 9),  // 模块单词索引 1
                Generators::choose(0, 9),  // 模块单词索引 2
                Generators::choose(0, 9),  // 模块单词索引 3
                Generators::choose(1, 3),  // 命令单词数量
                Generators::choose(0, 2),  // 命令分隔符索引
                Generators::choose(0, 9),  // 命令单词索引 1
                Generators::choose(0, 9),  // 命令单词索引 2
                Generators::choose(0, 9)   // 命令单词索引 3
            )
            ->then(function (
                int $modWordCount,
                int $modSepIdx,
                int $mw1,
                int $mw2,
                int $mw3,
                int $cmdWordCount,
                int $cmdSepIdx,
                int $cw1,
                int $cw2,
                int $cw3
            ) use ($moduleWords, $commandWords, $separators) {
                // 构建模块名输入
                $modSep = $separators[$modSepIdx];
                $modParts = array_slice([$moduleWords[$mw1], $moduleWords[$mw2], $moduleWords[$mw3]], 0, $modWordCount);
                $moduleInput = implode($modSep, $modParts);

                // 构建命令名输入
                $cmdSep = $separators[$cmdSepIdx];
                $cmdParts = array_slice([$commandWords[$cw1], $commandWords[$cw2], $commandWords[$cw3]], 0, $cmdWordCount);
                $commandInput = implode($cmdSep, $cmdParts);

                // 生成命令标识符
                $identifier = $this->buildCommandIdentifier($moduleInput, $commandInput);

                // 断言：恰好包含一个冒号分隔符
                $colonCount = substr_count($identifier, ':');
                $this->assertEquals(
                    1,
                    $colonCount,
                    "命令标识符 '$identifier' 应恰好包含一个冒号，实际有 $colonCount 个"
                );

                // 拆分为模块部分和命令部分
                [$modulePart, $commandPart] = explode(':', $identifier);

                // 断言：模块部分不为空
                $this->assertNotEmpty(
                    $modulePart,
                    "命令标识符 '$identifier' 的模块部分不应为空"
                );

                // 断言：命令部分不为空
                $this->assertNotEmpty(
                    $commandPart,
                    "命令标识符 '$identifier' 的命令部分不应为空"
                );

                // 断言：模块部分为纯小写字母和数字（无大写、无特殊字符）
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9]+$/',
                    $modulePart,
                    "命令标识符 '$identifier' 的模块部分 '$modulePart' 应仅包含小写字母和数字"
                );

                // 断言：命令部分为小写字母、数字和下划线（snake_case 格式）
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9][a-z0-9_]*[a-z0-9]$|^[a-z0-9]$/',
                    $commandPart,
                    "命令标识符 '$identifier' 的命令部分 '$commandPart' 应为合法 snake_case 格式"
                );

                // 断言：命令部分不包含连续下划线
                $this->assertStringNotContainsString(
                    '__',
                    $commandPart,
                    "命令标识符 '$identifier' 的命令部分 '$commandPart' 不应包含连续下划线"
                );

                // 断言：无大写字母
                $this->assertEquals(
                    strtolower($identifier),
                    $identifier,
                    "命令标识符 '$identifier' 不应包含大写字母"
                );
            });
    }

    /**
     * Property 5 补充: 使用随机字符串输入验证命令命名格式的鲁棒性
     *
     * 使用 Eris 的随机字符串生成器，过滤至少包含一个字母的字符串，
     * 验证即使输入不规范，生成的命令标识符仍满足格式约束。
     */
    public function testCommandNamingFormatWithRandomStrings(): void
    {
        // Feature: laravel-modules, Property 5: 命令命名格式一致性
        $this
            ->limitTo(100)
            ->forAll(
                Generators::suchThat(
                    function ($s) {
                        // 至少包含一个字母，确保 studlyCase 能产生有效输出
                        return is_string($s) && strlen($s) > 0 && preg_match('/[a-zA-Z]/', $s);
                    },
                    Generators::string()
                ),
                Generators::suchThat(
                    function ($s) {
                        // 至少包含一个字母，确保 toSnakeCase 能产生有效输出
                        return is_string($s) && strlen($s) > 0 && preg_match('/[a-zA-Z]/', $s);
                    },
                    Generators::string()
                )
            )
            ->then(function (string $moduleInput, string $commandInput) {
                $moduleName = $this->generator->studlyCase($moduleInput);
                $commandStudly = $this->generator->studlyCase($commandInput);

                // 跳过无法产生有效 studlyCase 输出的极端情况
                if (empty($moduleName) || empty($commandStudly)) {
                    return;
                }

                // 生成命令标识符
                $identifier = $this->buildCommandIdentifier($moduleInput, $commandInput);

                // 断言：恰好包含一个冒号
                $colonCount = substr_count($identifier, ':');
                $this->assertEquals(
                    1,
                    $colonCount,
                    "命令标识符 '$identifier'（输入: module='$moduleInput', command='$commandInput'）应恰好包含一个冒号"
                );

                // 拆分验证
                [$modulePart, $commandPart] = explode(':', $identifier);

                // 断言：两部分均不为空
                $this->assertNotEmpty($modulePart, "模块部分不应为空");
                $this->assertNotEmpty($commandPart, "命令部分不应为空");

                // 断言：模块部分为纯小写字母/数字
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9]+$/',
                    $modulePart,
                    "模块部分 '$modulePart' 应仅含小写字母和数字（输入: '$moduleInput'）"
                );

                // 断言：命令部分为小写字母/数字/下划线
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9_]+$/',
                    $commandPart,
                    "命令部分 '$commandPart' 应仅含小写字母、数字和下划线（输入: '$commandInput'）"
                );

                // 断言：整个标识符无大写字母
                $this->assertEquals(
                    strtolower($identifier),
                    $identifier,
                    "命令标识符 '$identifier' 不应包含任何大写字母"
                );

                // 断言：模块部分等于 strtolower(studlyCase(moduleInput))
                $expectedModulePart = strtolower($moduleName);
                $this->assertEquals(
                    $expectedModulePart,
                    $modulePart,
                    "模块部分 '$modulePart' 应等于 strtolower(studlyCase('$moduleInput')) = '$expectedModulePart'"
                );
            });
    }
}

<?php

namespace Thinkrix\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * 验证模块命令标识始终采用 module:command_snake 格式。
 */
class CommandNamingPropertyTest extends TestCase
{
    use TestTrait;

    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new ModuleGenerator($this->createMock(StubResolver::class));
    }

    private function computeSnakeCase(string $name): string
    {
        $studly = $this->generator->studlyCase($name);
        return strtolower((string) preg_replace('/([a-z\d])([A-Z])/', '$1_$2', $studly));
    }

    private function buildCommandIdentifier(string $moduleInput, string $commandInput): string
    {
        return strtolower($this->generator->studlyCase($moduleInput))
            . ':'
            . $this->computeSnakeCase($commandInput);
    }

    public function testCommandNamingFormatWithTypicalInputs(): void
    {
        $moduleWords = ['user', 'center', 'admin', 'blog', 'shop', 'api', 'core', 'auth', 'payment', 'order'];
        $commandWords = ['sync', 'data', 'clear', 'cache', 'import', 'export', 'generate', 'report', 'send', 'notify'];
        $separators = ['-', '_', ' '];

        $this->limitTo(100)->forAll(
            Generators::choose(1, 3),
            Generators::choose(0, 2),
            Generators::choose(0, 9),
            Generators::choose(0, 9),
            Generators::choose(0, 9),
            Generators::choose(1, 3),
            Generators::choose(0, 2),
            Generators::choose(0, 9),
            Generators::choose(0, 9),
            Generators::choose(0, 9)
        )->then(function (
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
        ) use ($moduleWords, $commandWords, $separators): void {
            $moduleInput = implode($separators[$modSepIdx], array_slice([$moduleWords[$mw1], $moduleWords[$mw2], $moduleWords[$mw3]], 0, $modWordCount));
            $commandInput = implode($separators[$cmdSepIdx], array_slice([$commandWords[$cw1], $commandWords[$cw2], $commandWords[$cw3]], 0, $cmdWordCount));
            $identifier = $this->buildCommandIdentifier($moduleInput, $commandInput);
            [$modulePart, $commandPart] = explode(':', $identifier);

            $this->assertSame(1, substr_count($identifier, ':'));
            $this->assertNotEmpty($modulePart);
            $this->assertNotEmpty($commandPart);
            $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $modulePart);
            $this->assertMatchesRegularExpression('/^[a-z0-9](?:[a-z0-9_]*[a-z0-9])?$/', $commandPart);
            $this->assertStringNotContainsString('__', $commandPart);
            $this->assertSame(strtolower($identifier), $identifier);
        });
    }

    public function testCommandNamingFormatWithRandomStrings(): void
    {
        $validString = static fn () => Generators::suchThat(
            static fn ($value): bool => is_string($value) && $value !== '' && preg_match('/[a-zA-Z]/', $value) === 1,
            Generators::string()
        );

        $this->limitTo(100)->forAll($validString(), $validString())
            ->then(function (string $moduleInput, string $commandInput): void {
                $moduleName = $this->generator->studlyCase($moduleInput);
                $commandName = $this->generator->studlyCase($commandInput);
                if ($moduleName === '' || $commandName === '') {
                    return;
                }

                $identifier = $this->buildCommandIdentifier($moduleInput, $commandInput);
                [$modulePart, $commandPart] = explode(':', $identifier);

                $this->assertSame(1, substr_count($identifier, ':'));
                $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $modulePart);
                $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $commandPart);
                $this->assertSame(strtolower($identifier), $identifier);
                $this->assertSame(strtolower($moduleName), $modulePart);
            });
    }
}

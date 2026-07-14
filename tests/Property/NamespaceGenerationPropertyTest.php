<?php

namespace Thinkrix\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/** 验证模块资源命名空间符合 PSR-4 结构。 */
class NamespaceGenerationPropertyTest extends TestCase
{
    use TestTrait;

    private ModuleGenerator $generator;

    private array $resourceTypes = [
        'controller', 'model', 'service', 'validate',
        'middleware', 'event', 'listener', 'command',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new ModuleGenerator($this->createMock(StubResolver::class));
    }

    public function testNamespaceGenerationFollowsPsr4Format(): void
    {
        $words = ['user', 'center', 'admin', 'blog', 'shop', 'api', 'core', 'auth', 'payment', 'order'];

        $this->limitTo(100)->forAll(
            Generators::choose(1, 3),
            Generators::choose(0, 9),
            Generators::choose(0, 9),
            Generators::choose(0, 9),
            Generators::choose(0, 7)
        )->then(function (int $wordCount, int $w1, int $w2, int $w3, int $typeIdx) use ($words): void {
            $parts = array_slice([$words[$w1], $words[$w2], $words[$w3]], 0, $wordCount);
            $moduleName = $this->generator->studlyCase(implode('-', $parts));
            $type = $this->resourceTypes[$typeIdx];
            $namespace = "app\\{$moduleName}\\{$type}";
            $segments = explode('\\', $namespace);

            $this->assertStringStartsWith('app\\', $namespace);
            $this->assertStringEndsWith("\\{$type}", $namespace);
            $this->assertCount(3, $segments);
            $this->assertNotEmpty($segments[0]);
            $this->assertNotEmpty($segments[1]);
            $this->assertNotEmpty($segments[2]);
            $this->assertStringNotContainsString('\\\\', $namespace);
            $this->assertSame($moduleName, $segments[1]);
        });
    }

    public function testNamespaceGenerationWithRandomStringModuleNames(): void
    {
        $this->limitTo(100)->forAll(
            Generators::suchThat(
                static fn ($value): bool => is_string($value) && $value !== '' && preg_match('/[a-zA-Z]/', $value) === 1,
                Generators::string()
            ),
            Generators::elements($this->resourceTypes)
        )->then(function (string $rawName, string $type): void {
            $moduleName = $this->generator->studlyCase($rawName);
            if ($moduleName === '') {
                return;
            }

            $namespace = "app\\{$moduleName}\\{$type}";
            $this->assertMatchesRegularExpression('/^app\\\\[A-Za-z0-9]+\\\\[a-z]+$/', $namespace);
            $this->assertStringNotContainsString('\\\\', $namespace);
            $this->assertSame($moduleName, explode('\\', $namespace)[1]);
        });
    }
}

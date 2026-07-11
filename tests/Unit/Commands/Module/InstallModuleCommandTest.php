<?php

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Thinkrix\Commands\Module\InstallModuleCommand;

class InstallModuleCommandTest extends TestCase
{
    public function testRegistryModuleIdIsNotRewritten(): void
    {
        self::assertSame('official.cms', $this->normalizeModuleName('official.cms', 'https://registry.example'));
    }

    private function normalizeModuleName(string $name, string $registry): string
    {
        $command = new InstallModuleCommand();
        $method = new ReflectionMethod($command, 'normalizeModuleNameForInstall');
        $method->setAccessible(true);

        return $method->invoke($command, $name, $registry);
    }
}

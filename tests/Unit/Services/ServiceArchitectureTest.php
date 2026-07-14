<?php

namespace Thinkrix\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/** 验证服务层不再依赖只提供静态工厂的空基类。 */
class ServiceArchitectureTest extends TestCase
{
    /** BaseService 删除后，具体服务应直接声明自己的职责。 */
    public function testServicesDoNotExtendEmptyBaseService(): void
    {
        $root = dirname(__DIR__, 3);

        self::assertFileDoesNotExist($root . '/src/Services/BaseService.php');

        foreach (['AuthService.php', 'ModuleService.php', 'PermissionService.php'] as $file) {
            $source = (string) file_get_contents($root . '/src/Services/' . $file);
            self::assertStringNotContainsString('extends BaseService', $source);
        }
    }
}

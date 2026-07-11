<?php

namespace Thinkrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Registry\RegistryModuleUpdatePlanner;

class RegistryModuleUpdatePlannerTest extends TestCase
{
    public function testPlansUpdateWhenTargetVersionIsNewer(): void
    {
        $plan = (new RegistryModuleUpdatePlanner('php', 'thinkphp'))->plan(
            ['id' => 'official.cms', 'version' => '1.0.0'],
            [
                'version' => '1.1.0',
                'manifest' => [
                    'id' => 'official.cms',
                    'version' => '1.1.0',
                    'adapter' => ['language' => 'php', 'framework' => 'thinkphp', 'status' => 'compatible'],
                ],
                'adapters' => [
                    ['language' => 'php', 'framework' => 'thinkphp', 'status' => 'compatible', 'package_type' => 'composer'],
                ],
            ]
        );

        self::assertTrue($plan['allowed']);
        self::assertSame('update_available', $plan['action']);
        self::assertSame('1.0.0', $plan['current_version']);
        self::assertSame('1.1.0', $plan['target_version']);
    }

    public function testNoopsWhenTargetVersionMatchesCurrentVersion(): void
    {
        $plan = (new RegistryModuleUpdatePlanner('php', 'thinkphp'))->plan(
            ['id' => 'official.cms', 'version' => '1.1.0'],
            [
                'version' => '1.1.0',
                'manifest' => [
                    'id' => 'official.cms',
                    'version' => '1.1.0',
                    'adapter' => ['language' => 'php', 'framework' => 'thinkphp', 'status' => 'compatible'],
                ],
                'adapters' => [
                    ['language' => 'php', 'framework' => 'thinkphp', 'status' => 'compatible'],
                ],
            ]
        );

        self::assertFalse($plan['allowed']);
        self::assertSame('already_current', $plan['action']);
    }

    public function testRejectsDowngradeByDefault(): void
    {
        $plan = (new RegistryModuleUpdatePlanner('php', 'thinkphp'))->plan(
            ['id' => 'official.cms', 'version' => '1.2.0'],
            [
                'version' => '1.1.0',
                'manifest' => [
                    'id' => 'official.cms',
                    'version' => '1.1.0',
                    'adapter' => ['language' => 'php', 'framework' => 'thinkphp', 'status' => 'compatible'],
                ],
                'adapters' => [
                    ['language' => 'php', 'framework' => 'thinkphp', 'status' => 'compatible'],
                ],
            ]
        );

        self::assertFalse($plan['allowed']);
        self::assertSame('downgrade_blocked', $plan['action']);
    }

    public function testAllowsDowngradeWhenExplicitlyRequested(): void
    {
        $plan = (new RegistryModuleUpdatePlanner('php', 'thinkphp'))->plan(
            ['id' => 'official.cms', 'version' => '1.2.0'],
            [
                'version' => '1.1.0',
                'manifest' => [
                    'id' => 'official.cms',
                    'version' => '1.1.0',
                    'adapter' => ['language' => 'php', 'framework' => 'thinkphp', 'status' => 'compatible'],
                ],
                'adapters' => [
                    ['language' => 'php', 'framework' => 'thinkphp', 'status' => 'compatible'],
                ],
            ],
            true
        );

        self::assertTrue($plan['allowed']);
        self::assertSame('downgrade_allowed', $plan['action']);
        self::assertSame('1.2.0', $plan['current_version']);
        self::assertSame('1.1.0', $plan['target_version']);
    }
}

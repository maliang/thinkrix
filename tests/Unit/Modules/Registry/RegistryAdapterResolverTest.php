<?php

namespace Thinkrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Registry\RegistryAdapterResolver;

class RegistryAdapterResolverTest extends TestCase
{
    public function testResolvesInstallableThinkphpAdapter(): void
    {
        $resolver = new RegistryAdapterResolver('php', 'thinkphp');

        $result = $resolver->resolve([
            'version' => '1.0.0',
            'adapters' => [
                [
                    'language' => 'php',
                    'framework' => 'laravel',
                    'status' => 'stable',
                    'package_type' => 'composer',
                    'package_url' => 'https://registry.example/modules/official.cms-laravel.zip',
                ],
                [
                    'language' => 'php',
                    'framework' => 'thinkphp',
                    'status' => 'compatible',
                    'package_type' => 'composer',
                    'package_url' => 'https://registry.example/modules/official.cms-thinkphp.zip',
                    'checksum' => 'sha256:def',
                ],
            ],
        ]);

        self::assertTrue($result['installable']);
        self::assertSame('php', $result['adapter']['language']);
        self::assertSame('thinkphp', $result['adapter']['framework']);
        self::assertSame('compatible', $result['adapter']['status']);
        self::assertSame('composer', $result['adapter']['package_type']);
        self::assertSame('https://registry.example/modules/official.cms-thinkphp.zip', $result['adapter']['package_url']);
    }

    public function testRejectsUnsupportedThinkphpAdapter(): void
    {
        $resolver = new RegistryAdapterResolver('php', 'thinkphp');

        $result = $resolver->resolve([
            'adapters' => [
                [
                    'language' => 'php',
                    'framework' => 'thinkphp',
                    'status' => 'unsupported',
                ],
            ],
        ]);

        self::assertFalse($result['installable']);
        self::assertSame('adapter_not_installable', $result['reason']);
        self::assertStringContainsString('unsupported', $result['message']);
    }

    public function testRejectsMissingThinkphpAdapter(): void
    {
        $resolver = new RegistryAdapterResolver('php', 'thinkphp');

        $result = $resolver->resolve([
            'adapters' => [
                [
                    'language' => 'php',
                    'framework' => 'laravel',
                    'status' => 'stable',
                ],
            ],
        ]);

        self::assertFalse($result['installable']);
        self::assertSame('adapter_missing', $result['reason']);
        self::assertStringContainsString('thinkphp', $result['message']);
        self::assertStringContainsString('available adapters: php/laravel', $result['message']);
    }
}

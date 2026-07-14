<?php

namespace Thinkrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Registry\RegistryClient;

/** 验证统一市场客户端的认证、地址和下载信任边界。 */
class RegistryClientTest extends TestCase
{
    /** JSON 请求应携带 Auth Key 并禁止重定向。 */
    public function testRequestsRegistryWithAuthKeyAndRedirectsDisabled(): void
    {
        $captured = [];
        $client = new RegistryClient('https://registry.example/api/', 'trx_test', 15,
            function (string $method, string $url, array $options) use (&$captured): array {
                $captured = compact('method', 'url', 'options');
                return ['status' => 200, 'body' => '{"code":0,"data":{"items":[]}}'];
            });

        $result = $client->getJson('/registry/modules', ['language' => 'php']);

        self::assertTrue($result['ok']);
        self::assertSame('https://registry.example/api/registry/modules?language=php', $captured['url']);
        self::assertContains('Authorization: Bearer trx_test', $captured['options']['headers']);
        self::assertSame(0, $captured['options']['follow_location']);
        self::assertSame(0, $captured['options']['max_redirects']);
    }

    /** 跨域包地址不得触发请求。 */
    public function testRejectsCrossOriginPackageUrl(): void
    {
        $called = false;
        $client = new RegistryClient('https://registry.example/api', 'secret', transport:
            function () use (&$called): array { $called = true; return ['status' => 200, 'body' => 'zip']; });

        self::assertNull($client->download('https://attacker.example/module.zip'));
        self::assertFalse($called);
    }
}

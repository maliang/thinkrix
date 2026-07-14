<?php

namespace Thinkrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;

class RegistryConfigTest extends TestCase
{
    public function testModuleMarketIsTheOnlyRegistryConfiguration(): void
    {
        $config = (string) file_get_contents(__DIR__ . '/../../../../config/thinkrix.php');

        self::assertStringContainsString("'module_market' => [", $config);
        self::assertStringContainsString("'url' => env('THINKRIX_MODULE_MARKET_URL'", $config);
        self::assertStringContainsString("'auth_key' => env('TRIX_AUTH_KEY', '')", $config);
        self::assertStringContainsString("'signature_key' => env('THINKRIX_MODULE_MARKET_SIGNATURE_KEY', '')", $config);
        self::assertStringNotContainsString("'module_registry' => [", $config);
    }
}

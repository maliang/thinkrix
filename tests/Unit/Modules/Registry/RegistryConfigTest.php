<?php

namespace Thinkrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;

class RegistryConfigTest extends TestCase
{
    public function testModuleRegistryConfigProvidesUrlAndSignatureKeyDefaults(): void
    {
        $config = (string) file_get_contents(__DIR__ . '/../../../../config/thinkrix.php');

        self::assertStringContainsString("'module_registry' => [", $config);
        self::assertStringContainsString("'url' => env('THINKRIX_MODULE_REGISTRY_URL', '')", $config);
        self::assertStringContainsString("'signature_key' => env('THINKRIX_MODULE_REGISTRY_SIGNATURE_KEY', '')", $config);
    }
}

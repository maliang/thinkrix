<?php

namespace Thinkrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Registry\RegistryClient;
use Thinkrix\Modules\Registry\RegistryPackagePipeline;
use ZipArchive;

/** 验证下载、暂存和嵌套 Manifest 校验使用同一安全管线。 */
class RegistryPackagePipelineTest extends TestCase
{
    public function testPreparesAValidThinkphpPackage(): void
    {
        $version = '1.0.' . random_int(100, 999);
        $package = $this->package($version);
        $client = new RegistryClient('https://registry.example', transport: fn (): array => ['status' => 200, 'body' => $package]);
        $result = (new RegistryPackagePipeline($client))->prepare([
            'language' => 'php', 'framework' => 'thinkphp',
            'package_url' => 'https://registry.example/packages/cms.zip',
            'checksum' => 'sha256:' . hash('sha256', $package),
        ], 'official.cms', $version);

        self::assertTrue($result['ok']);
        self::assertSame('official.cms/module.json', $result['manifest']);
        self::assertSame(['writes_files' => true], $result['security']);
    }

    private function package(string $version): string
    {
        $path = tempnam(sys_get_temp_dir(), 'thinkrix-pipeline-') . '.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('official.cms/module.json', json_encode([
            'name' => 'CMS', 'enabled' => false,
            'trix' => [
                'schema_version' => 'trix.module.v1', 'id' => 'official.cms', 'name' => 'CMS',
                'version' => $version, 'type' => 'contract',
                'adapter' => ['language' => 'php', 'framework' => 'thinkphp', 'status' => 'compatible'],
                'security' => ['writes_files' => true],
            ],
        ], JSON_THROW_ON_ERROR));
        $zip->close();
        return (string) file_get_contents($path);
    }
}

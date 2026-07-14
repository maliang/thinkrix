<?php

namespace Thinkrix\Tests\Unit\Modules\Manifest;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Manifest\ModuleManifestLoader;

class ModuleManifestLoaderTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-manifest-' . uniqid('', true);
        mkdir($this->tempPath, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    /** @test */
    public function it_loads_module_json_manifest(): void
    {
        $this->writeJson('module.json', [
            'name' => 'Members',
            'enabled' => false,
            'trix' => [
                'schema_version' => 'trix.module.v1',
                'id' => 'official.members',
                'name' => 'Members',
                'version' => '1.0.0',
                'type' => 'contract',
                'adapter' => [
                    'language' => 'php',
                    'framework' => 'thinkphp',
                    'status' => 'compatible',
                ],
            ],
        ]);

        $manifest = (new ModuleManifestLoader())->loadFromPath($this->tempPath);

        $this->assertNotNull($manifest);
        $this->assertSame('official.members', $manifest->id());
    }

    /** @test */
    public function it_does_not_treat_native_root_fields_as_trix_protocol(): void
    {
        $this->writeJson('module.json', [
            'name' => 'Members',
            'title' => 'Members Module',
            'version' => '0.1.0',
        ]);

        $manifest = (new ModuleManifestLoader())->loadFromPath($this->tempPath);

        $this->assertNull($manifest);
    }

    /** @test */
    public function it_rejects_invalid_json_manifest(): void
    {
        file_put_contents($this->tempPath . DIRECTORY_SEPARATOR . 'module.json', '{bad json');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON manifest');

        (new ModuleManifestLoader())->loadFromPath($this->tempPath);
    }

    /** @test */
    public function it_rejects_invalid_nested_trix_manifest(): void
    {
        $this->writeJson('module.json', ['name' => 'Members', 'trix' => ['schema_version' => 'bad']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('trix.schema_version');

        (new ModuleManifestLoader())->loadFromPath($this->tempPath);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJson(string $filename, array $data): void
    {
        file_put_contents(
            $this->tempPath . DIRECTORY_SEPARATOR . $filename,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $this->deleteDirectory($fullPath);
                continue;
            }

            unlink($fullPath);
        }

        rmdir($path);
    }
}

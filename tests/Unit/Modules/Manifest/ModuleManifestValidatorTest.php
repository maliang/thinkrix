<?php

namespace Thinkrix\Tests\Unit\Modules\Manifest;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Manifest\ModuleManifestValidator;

class ModuleManifestValidatorTest extends TestCase
{
    /** @test */
    public function it_accepts_valid_manifest_and_rejects_invalid_adapter_install(): void
    {
        $valid = ModuleManifestValidator::validate($this->manifest('compatible'));
        $invalid = ModuleManifestValidator::validateForAdapter($this->manifest('planned'), 'php', 'thinkphp');

        $this->assertSame([], $valid);
        $this->assertArrayHasKey('adapter.status', $invalid);
    }

    /** @test */
    public function it_rejects_missing_required_fields_and_bad_entries(): void
    {
        $errors = ModuleManifestValidator::validate([
            'schema_version' => 'bad',
            'type' => 'other',
            'adapter' => [
                'language' => 'php',
                'framework' => 'thinkphp',
                'status' => 'bad',
            ],
            'menus' => [
                ['key' => 'cms.posts'],
            ],
            'permissions' => [
                ['name' => 'cms.posts.view'],
            ],
            'schemas' => [
                ['key' => 'posts.index'],
            ],
        ]);

        $this->assertArrayHasKey('schema_version', $errors);
        $this->assertArrayHasKey('id', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('version', $errors);
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('adapter.status', $errors);
        $this->assertArrayHasKey('menus.0.title', $errors);
        $this->assertArrayHasKey('permissions.0.title', $errors);
        $this->assertArrayHasKey('schemas.0.title', $errors);
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(string $status): array
    {
        return [
            'schema_version' => 'trix.module.v1',
            'id' => 'official.cms',
            'name' => 'CMS',
            'version' => '1.0.0',
            'type' => 'contract',
            'adapter' => [
                'language' => 'php',
                'language_version' => '>=8.1',
                'framework' => 'thinkphp',
                'framework_version' => '>=8.0',
                'status' => $status,
            ],
        ];
    }
}

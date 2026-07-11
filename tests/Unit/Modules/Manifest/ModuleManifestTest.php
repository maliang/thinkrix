<?php

namespace Thinkrix\Tests\Unit\Modules\Manifest;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Manifest\ModuleManifest;

class ModuleManifestTest extends TestCase
{
    /** @test */
    public function it_can_create_manifest_from_array(): void
    {
        $manifest = ModuleManifest::fromArray([
            'schema_version' => 'trix.module.v1',
            'id' => 'official.cms',
            'name' => 'CMS',
            'version' => '1.0.0',
            'type' => 'contract',
            'logo' => 'assets/logo.svg',
            'thumbnail' => 'assets/cover.png',
            'author' => 'Trix Official',
            'author_url' => 'https://www.trixmore.lav',
            'adapter' => [
                'language' => 'php',
                'language_version' => '>=8.1',
                'framework' => 'thinkphp',
                'framework_version' => '>=8.0',
                'status' => 'compatible',
            ],
            'menus' => [
                ['key' => 'cms.posts', 'title' => '鏂囩珷绠＄悊', 'path' => '/cms/posts'],
            ],
            'permissions' => [
                ['name' => 'cms.posts.view', 'title' => '鏌ョ湅鏂囩珷'],
            ],
            'schemas' => [
                ['key' => 'posts.index', 'title' => '鏂囩珷鍒楄〃', 'path' => 'schemas/posts.index.json'],
            ],
            'security' => [
                'writes_files' => true,
            ],
        ]);

        $this->assertSame('official.cms', $manifest->id());
        $this->assertSame('CMS', $manifest->name());
        $this->assertSame('1.0.0', $manifest->version());
        $this->assertSame('contract', $manifest->type());
        $this->assertSame('assets/logo.svg', $manifest->logo());
        $this->assertSame('assets/cover.png', $manifest->thumbnail());
        $this->assertSame('Trix Official', $manifest->author());
        $this->assertSame('https://www.trixmore.lav', $manifest->authorUrl());
        $this->assertSame('php', $manifest->adapterLanguage());
        $this->assertSame('thinkphp', $manifest->adapterFramework());
        $this->assertSame('compatible', $manifest->adapterStatus());
        $this->assertSame('鏂囩珷绠＄悊', $manifest->menus()[0]['title']);
        $this->assertSame('cms.posts.view', $manifest->permissions()[0]['name']);
        $this->assertSame('posts.index', $manifest->schemas()[0]['key']);
        $this->assertTrue($manifest->security()['writes_files']);
    }
}

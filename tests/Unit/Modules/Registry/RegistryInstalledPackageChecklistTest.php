<?php

namespace Thinkrix\Tests\Unit\Modules\Registry;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Registry\RegistryInstalledPackageChecklist;

class RegistryInstalledPackageChecklistTest extends TestCase
{
    public function testBuildsThinkphpReviewChecklistFromCopiedModule(): void
    {
        $modulePath = $this->makeModule([
            'composer.json' => '{"extra":{"think":{"services":["Modules\\\\Cms\\\\Service"]}}}',
            'Service.php' => '<?php',
            'database/migrations/2026_01_01_000000_create_posts_table.php' => '<?php',
            'database/seeders/CmsSeeder.php' => '<?php',
        ]);

        $result = (new RegistryInstalledPackageChecklist())->build($modulePath, 'official.cms');

        self::assertTrue($result['has_composer']);
        self::assertSame(1, $result['provider_count']);
        self::assertSame(1, $result['migration_count']);
        self::assertSame(1, $result['seeder_count']);
        self::assertContains('Review composer.json and merge ThinkPHP service/autoload settings if needed.', $result['todos']);
        self::assertContains('Run migrations manually after review, for example: php think migrate:run', $result['commands']);
        self::assertContains('Run seeders manually after review, for example: php think seed:run', $result['commands']);
    }

    /**
     * @param array<string, string> $files
     */
    private function makeModule(array $files): string
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-checklist-module-' . uniqid('', true);

        foreach ($files as $path => $content) {
            $fullPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            file_put_contents($fullPath, $content);
        }

        return $root;
    }
}

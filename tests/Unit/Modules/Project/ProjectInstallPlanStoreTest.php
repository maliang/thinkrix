<?php

namespace Thinkrix\Tests\Unit\Modules\Project;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Project\ProjectInstallPlanStore;

class ProjectInstallPlanStoreTest extends TestCase
{
    public function testAppliesAndReadsSingleProjectRuntimeConfiguration(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-project-store-' . uniqid();
        $path = $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'trix-project.php';
        $store = new ProjectInstallPlanStore($path);

        self::assertSame($path, $store->apply([
            'project' => 'official.mall-starter',
            'version' => '1.0.0',
            'project_config' => ['site_name' => 'Mall Demo'],
            'contract_bindings' => ['user.account' => ['provider_module' => 'official.user']],
            'setup' => ['commands' => ['php think migrate:run']],
            'modules' => [
                ['id' => 'official.user', 'selected_version' => '2.0.0', 'config' => ['guard' => 'member']],
            ],
        ]));

        $config = $store->read();
        self::assertSame('official.mall-starter', $config['id']);
        self::assertSame('2.0.0', $config['modules']['official.user']['version']);
        self::assertSame(['guard' => 'member'], $config['modules']['official.user']['config']);
        self::assertFileExists($path);
        self::assertFileDoesNotExist($root . DIRECTORY_SEPARATOR . 'install-plan.json');
    }
}

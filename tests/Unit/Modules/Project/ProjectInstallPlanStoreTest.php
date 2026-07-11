<?php

namespace Thinkrix\Tests\Unit\Modules\Project;

use PHPUnit\Framework\TestCase;
use Thinkrix\Modules\Project\ProjectInstallPlanStore;

class ProjectInstallPlanStoreTest extends TestCase
{
    public function testSavesAndReadsProjectInstallPlanArtifacts(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-project-store-' . uniqid();
        $store = new ProjectInstallPlanStore($root);

        $paths = $store->save('official.mall-starter', '1.0.0', [
            'project' => 'official.mall-starter',
            'version' => '1.0.0',
            'project_config' => ['site_name' => 'Mall Demo'],
            'contract_bindings' => [['key' => 'user.account', 'provider_module' => 'official.user']],
            'setup' => ['commands' => ['php think migrate:run']],
            'modules' => [
                ['id' => 'official.user', 'config' => ['guard' => 'member']],
            ],
        ]);

        self::assertFileExists($paths['install_plan']);
        self::assertFileExists($paths['project_config']);
        self::assertFileExists($paths['contract_bindings']);
        self::assertSame(['site_name' => 'Mall Demo'], $store->projectConfig('official.mall-starter', '1.0.0'));
        self::assertSame(['guard' => 'member'], $store->moduleConfig('official.mall-starter', '1.0.0', 'official.user'));
        self::assertSame([['key' => 'user.account', 'provider_module' => 'official.user']], $store->contractBindings('official.mall-starter', '1.0.0'));
    }
}

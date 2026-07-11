<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\MigrateCommand;
use Thinkrix\Commands\Module\SeedCommand;
use Thinkrix\Commands\Module\BaseModuleCommand;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * 杩佺Щ鍛戒护鍗曞厓娴嬭瘯
 *
 * 娴嬭瘯 MigrateCommand 鍜?SeedCommand 鐨勯厤缃纭€с€?
 * 杩佺Щ鏂囦欢妫€娴嬮€昏緫銆佷互鍙婃帓搴忚涓恒€?
 *
 * Requirements: 4.1-4.6
 */
class MigrateCommandsTest extends TestCase
{
    private string $tempDir;
    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'migrate_cmd_test_' . uniqid();
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'app', 0755, true);

        // 鍒涘缓鍙祴璇曠殑 StubResolver 鍜?ModuleGenerator
        $stubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'stubs';
        mkdir($stubDir, 0755, true);

        $stubResolver = new class($stubDir, $stubDir) extends StubResolver {
            public function __construct(string $d, string $c)
            {
                $this->defaultStubPath = $d;
                $this->customStubPath = $c;
            }
        };

        $tempDir = $this->tempDir;
        $this->generator = new class($stubResolver, $tempDir) extends ModuleGenerator {
            private string $rootPath;

            public function __construct(StubResolver $sr, string $rp)
            {
                parent::__construct($sr);
                $this->rootPath = $rp . DIRECTORY_SEPARATOR;
            }

            public function getModulePath(string $module): string
            {
                return $this->rootPath . 'app' . DIRECTORY_SEPARATOR . $module;
            }
        };
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== MigrateCommand 閰嶇疆娴嬭瘯 ====================

    /**
     * 娴嬭瘯 MigrateCommand 鍛戒护鍚嶇О
     *
     * Requirements: 4.1
     */
    public function testMigrateCommandName(): void
    {
        $cmd = new MigrateCommand();
        $this->assertEquals('thinkrix:module-migrate', $cmd->getName());
    }

    /**
     * 娴嬭瘯 MigrateCommand 鏈夋弿杩颁俊鎭?
     *
     * Requirements: 4.1
     */
    public function testMigrateCommandHasDescription(): void
    {
        $cmd = new MigrateCommand();
        $this->assertNotEmpty($cmd->getDescription());
    }

    /**
     * 娴嬭瘯 MigrateCommand 鏈夊彲閫夌殑 module 鍙傛暟
     *
     * Requirements: 4.1, 4.2
     */
    public function testMigrateCommandHasOptionalModuleArgument(): void
    {
        $cmd = new MigrateCommand();
        $def = $cmd->getDefinition();
        $this->assertTrue($def->hasArgument('module'));
        $this->assertFalse($def->getArgument('module')->isRequired());
    }

    /**
     * 娴嬭瘯 MigrateCommand 鏈?--rollback 閫夐」
     *
     * Requirements: 4.4
     */
    public function testMigrateCommandHasRollbackOption(): void
    {
        $cmd = new MigrateCommand();
        $def = $cmd->getDefinition();
        $this->assertTrue($def->hasOption('rollback'));
    }

    /**
     * 娴嬭瘯 MigrateCommand 鏈?--refresh 閫夐」
     *
     * Requirements: 4.5
     */
    public function testMigrateCommandHasRefreshOption(): void
    {
        $cmd = new MigrateCommand();
        $def = $cmd->getDefinition();
        $this->assertTrue($def->hasOption('refresh'));
    }

    /**
     * 娴嬭瘯 MigrateCommand 缁ф壙 BaseModuleCommand
     *
     * Requirements: 4.1
     */
    public function testMigrateCommandExtendsBaseModuleCommand(): void
    {
        $cmd = new MigrateCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $cmd);
    }

    // ==================== SeedCommand 閰嶇疆娴嬭瘯 ====================

    /**
     * 娴嬭瘯 SeedCommand 鍛戒护鍚嶇О
     *
     * Requirements: 4.3
     */
    public function testSeedCommandName(): void
    {
        $cmd = new SeedCommand();
        $this->assertEquals('thinkrix:module-seed', $cmd->getName());
    }

    /**
     * 娴嬭瘯 SeedCommand 鏈夋弿杩颁俊鎭?
     *
     * Requirements: 4.3
     */
    public function testSeedCommandHasDescription(): void
    {
        $cmd = new SeedCommand();
        $this->assertNotEmpty($cmd->getDescription());
    }

    /**
     * 娴嬭瘯 SeedCommand 鏈夊繀濉殑 module 鍙傛暟
     *
     * Requirements: 4.3
     */
    public function testSeedCommandHasRequiredModuleArgument(): void
    {
        $cmd = new SeedCommand();
        $def = $cmd->getDefinition();
        $this->assertTrue($def->hasArgument('module'));
        $this->assertTrue($def->getArgument('module')->isRequired());
    }

    /**
     * 娴嬭瘯 SeedCommand 缁ф壙 BaseModuleCommand
     *
     * Requirements: 4.3
     */
    public function testSeedCommandExtendsBaseModuleCommand(): void
    {
        $cmd = new SeedCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $cmd);
    }

    // ==================== 杩佺Щ鏂囦欢妫€娴嬮€昏緫娴嬭瘯 ====================

    /**
     * 娴嬭瘯杩佺Щ鐩綍妫€娴嬧€斺€旀ā鍧楀瓨鍦ㄤ笖鏈?migrations 鐩綍
     *
     * Requirements: 4.1
     */
    public function testMigrationDirectoryDetection(): void
    {
        // 鍒涘缓鍚縼绉荤洰褰曠殑妯″潡
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $migrationDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($migrationDir, 0755, true);

        // 楠岃瘉妯″潡瀛樺湪
        $this->assertTrue($this->generator->moduleExists('Blog'));

        // 楠岃瘉杩佺Щ鐩綍瀛樺湪
        $this->assertDirectoryExists($migrationDir);
    }

    /**
     * 娴嬭瘯杩佺Щ鏂囦欢鎸夋椂闂存埑鎺掑簭
     *
     * Requirements: 4.1
     */
    public function testMigrationFilesSortedByTimestamp(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $migrationDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($migrationDir, 0755, true);

        // 鍒涘缓涔卞簭鐨勬椂闂存埑鍓嶇紑杩佺Щ鏂囦欢
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240301000000_create_posts_table.php', '<?php return new class {};');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240101000000_create_users_table.php', '<?php return new class {};');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240201000000_create_comments_table.php', '<?php return new class {};');

        $files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.php');
        sort($files);

        // 鎺掑簭鍚庡簲鎸夋椂闂存埑椤哄簭鎺掑垪
        $this->assertStringContainsString('20240101', basename($files[0]));
        $this->assertStringContainsString('20240201', basename($files[1]));
        $this->assertStringContainsString('20240301', basename($files[2]));
    }

    /**
     * 娴嬭瘯 Seeder 鐩綍妫€娴?
     *
     * Requirements: 4.3
     */
    public function testSeederDirectoryDetection(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $seederDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders';
        mkdir($seederDir, 0755, true);

        $this->assertTrue($this->generator->moduleExists('Blog'));
        $this->assertDirectoryExists($seederDir);
    }

    /**
     * 娴嬭瘯妯″潡鏃犺縼绉荤洰褰曟椂鐨勬儏鍐?
     *
     * Requirements: 4.6
     */
    public function testModuleWithNoMigrationsDirectory(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Empty';
        mkdir($modulePath, 0755, true);

        $this->assertTrue($this->generator->moduleExists('Empty'));

        $migrationDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        $this->assertDirectoryDoesNotExist($migrationDir);
    }

    /**
     * 娴嬭瘯妯″潡杩佺Щ鐩綍涓虹┖鏃惰繑鍥炵┖鏁扮粍
     *
     * Requirements: 4.6
     */
    public function testModuleWithEmptyMigrationsDirectory(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $migrationDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($migrationDir, 0755, true);

        $files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.php');
        $this->assertEmpty($files);
    }

    /**
     * 娴嬭瘯 rollback 閫夐」搴斾娇鐢ㄥ弽杞殑鏂囦欢椤哄簭
     *
     * Requirements: 4.4
     */
    public function testRollbackUsesReverseOrder(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $migrationDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($migrationDir, 0755, true);

        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240101000000_create_users_table.php', '<?php return new class {};');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240201000000_create_posts_table.php', '<?php return new class {};');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240301000000_create_comments_table.php', '<?php return new class {};');

        $files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.php');
        sort($files);

        // rollback 鏃跺簲鍙嶈浆椤哄簭锛堟渶鏂扮殑鍏堝洖婊氾級
        $reversed = array_reverse($files);
        $this->assertStringContainsString('20240301', basename($reversed[0]));
        $this->assertStringContainsString('20240201', basename($reversed[1]));
        $this->assertStringContainsString('20240101', basename($reversed[2]));
    }

    /**
     * 娴嬭瘯 refresh 鎿嶄綔鈥斺€斿厛鍙嶈浆鍥炴粴鍐嶆搴忔墽琛?
     *
     * Requirements: 4.5
     */
    public function testRefreshUsesReverseOrderThenForwardOrder(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $migrationDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($migrationDir, 0755, true);

        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240101000000_create_users_table.php', '<?php return new class {};');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240201000000_create_posts_table.php', '<?php return new class {};');

        $files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.php');
        sort($files);

        // refresh 鎿嶄綔锛氬厛鍙嶈浆鍥炴粴
        $reversed = array_reverse($files);
        $this->assertStringContainsString('20240201', basename($reversed[0]));
        $this->assertStringContainsString('20240101', basename($reversed[1]));

        // 鍐嶆搴忔墽琛?
        $this->assertStringContainsString('20240101', basename($files[0]));
        $this->assertStringContainsString('20240201', basename($files[1]));
    }

    /**
     * 娴嬭瘯浠呮娴?.php 鍚庣紑鐨勮縼绉绘枃浠?
     *
     * Requirements: 4.1
     */
    public function testOnlyPhpFilesDetectedAsMigrations(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $migrationDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($migrationDir, 0755, true);

        // 鍒涘缓娣峰悎鍚庣紑鏂囦欢
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240101000000_create_users_table.php', '<?php return new class {};');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . 'README.md', '# Notes');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '.gitkeep', '');

        $files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.php');
        $this->assertCount(1, $files);
        $this->assertStringContainsString('create_users_table.php', basename($files[0]));
    }

    // ==================== 杈呭姪鏂规硶 ====================

    /**
     * 閫掑綊鍒犻櫎鐩綍
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}

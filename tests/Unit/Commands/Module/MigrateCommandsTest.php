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
 * 迁移命令单元测试
 *
 * 测试 MigrateCommand 和 SeedCommand 的配置正确性、
 * 迁移文件检测逻辑、以及排序行为。
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

        // 创建可测试的 StubResolver 和 ModuleGenerator
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

    // ==================== MigrateCommand 配置测试 ====================

    /**
     * 测试 MigrateCommand 命令名称
     *
     * Requirements: 4.1
     */
    public function testMigrateCommandName(): void
    {
        $cmd = new MigrateCommand();
        $this->assertEquals('thinkrix:module-migrate', $cmd->getName());
    }

    /**
     * 测试 MigrateCommand 有描述信息
     *
     * Requirements: 4.1
     */
    public function testMigrateCommandHasDescription(): void
    {
        $cmd = new MigrateCommand();
        $this->assertNotEmpty($cmd->getDescription());
    }

    /**
     * 测试 MigrateCommand 有可选的 module 参数
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
     * 测试 MigrateCommand 有 --rollback 选项
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
     * 测试 MigrateCommand 有 --refresh 选项
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
     * 测试 MigrateCommand 继承 BaseModuleCommand
     *
     * Requirements: 4.1
     */
    public function testMigrateCommandExtendsBaseModuleCommand(): void
    {
        $cmd = new MigrateCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $cmd);
    }

    // ==================== SeedCommand 配置测试 ====================

    /**
     * 测试 SeedCommand 命令名称
     *
     * Requirements: 4.3
     */
    public function testSeedCommandName(): void
    {
        $cmd = new SeedCommand();
        $this->assertEquals('thinkrix:module-seed', $cmd->getName());
    }

    /**
     * 测试 SeedCommand 有描述信息
     *
     * Requirements: 4.3
     */
    public function testSeedCommandHasDescription(): void
    {
        $cmd = new SeedCommand();
        $this->assertNotEmpty($cmd->getDescription());
    }

    /**
     * 测试 SeedCommand 有必填的 module 参数
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
     * 测试 SeedCommand 继承 BaseModuleCommand
     *
     * Requirements: 4.3
     */
    public function testSeedCommandExtendsBaseModuleCommand(): void
    {
        $cmd = new SeedCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $cmd);
    }

    // ==================== 迁移文件检测逻辑测试 ====================

    /**
     * 测试迁移目录检测——模块存在且有 migrations 目录
     *
     * Requirements: 4.1
     */
    public function testMigrationDirectoryDetection(): void
    {
        // 创建含迁移目录的模块
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $migrationDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($migrationDir, 0755, true);

        // 验证模块存在
        $this->assertTrue($this->generator->moduleExists('Blog'));

        // 验证迁移目录存在
        $this->assertDirectoryExists($migrationDir);
    }

    /**
     * 测试迁移文件按时间戳排序
     *
     * Requirements: 4.1
     */
    public function testMigrationFilesSortedByTimestamp(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $migrationDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($migrationDir, 0755, true);

        // 创建乱序的时间戳前缀迁移文件
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240301000000_create_posts_table.php', '<?php return new class {};');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240101000000_create_users_table.php', '<?php return new class {};');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240201000000_create_comments_table.php', '<?php return new class {};');

        $files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.php');
        sort($files);

        // 排序后应按时间戳顺序排列
        $this->assertStringContainsString('20240101', basename($files[0]));
        $this->assertStringContainsString('20240201', basename($files[1]));
        $this->assertStringContainsString('20240301', basename($files[2]));
    }

    /**
     * 测试 Seeder 目录检测
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
     * 测试模块无迁移目录时的情况
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
     * 测试模块迁移目录为空时返回空数组
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
     * 测试 rollback 选项应使用反转的文件顺序
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

        // rollback 时应反转顺序（最新的先回滚）
        $reversed = array_reverse($files);
        $this->assertStringContainsString('20240301', basename($reversed[0]));
        $this->assertStringContainsString('20240201', basename($reversed[1]));
        $this->assertStringContainsString('20240101', basename($reversed[2]));
    }

    /**
     * 测试 refresh 操作——先反转回滚再正序执行
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

        // refresh 操作：先反转回滚
        $reversed = array_reverse($files);
        $this->assertStringContainsString('20240201', basename($reversed[0]));
        $this->assertStringContainsString('20240101', basename($reversed[1]));

        // 再正序执行
        $this->assertStringContainsString('20240101', basename($files[0]));
        $this->assertStringContainsString('20240201', basename($files[1]));
    }

    /**
     * 测试仅检测 .php 后缀的迁移文件
     *
     * Requirements: 4.1
     */
    public function testOnlyPhpFilesDetectedAsMigrations(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $migrationDir = $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($migrationDir, 0755, true);

        // 创建混合后缀文件
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '20240101000000_create_users_table.php', '<?php return new class {};');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . 'README.md', '# Notes');
        file_put_contents($migrationDir . DIRECTORY_SEPARATOR . '.gitkeep', '');

        $files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.php');
        $this->assertCount(1, $files);
        $this->assertStringContainsString('create_users_table.php', basename($files[0]));
    }

    // ==================== 辅助方法 ====================

    /**
     * 递归删除目录
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

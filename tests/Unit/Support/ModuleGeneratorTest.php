<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * ModuleGenerator 鍗曞厓娴嬭瘯
 */
class ModuleGeneratorTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;
    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        // 鍒涘缓涓存椂鐩綍妯℃嫙椤圭洰缁撴瀯
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'module_generator_test_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'app', 0755, true);

        // 鍒涘缓鍩虹 stub 妯℃澘
        $this->createStubFiles();

        // 浣跨敤鍙祴璇曠殑 StubResolver
        $stubResolver = $this->createStubResolver();

        // 浣跨敤鍙祴璇曠殑 ModuleGenerator锛堣鐩?getModulePath 浣跨敤涓存椂鐩綍锛?
        $this->generator = $this->createGenerator($stubResolver);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== studlyCase 娴嬭瘯 ====================

    /**
     * 娴嬭瘯杩炲瓧绗﹀垎闅旂殑鍚嶇О杞崲
     */
    public function testStudlyCaseConvertsHyphenSeparated(): void
    {
        $this->assertEquals('UserCenter', $this->generator->studlyCase('user-center'));
    }

    /**
     * 娴嬭瘯涓嬪垝绾垮垎闅旂殑鍚嶇О杞崲
     */
    public function testStudlyCaseConvertsUnderscoreSeparated(): void
    {
        $this->assertEquals('UserCenter', $this->generator->studlyCase('user_center'));
    }

    /**
     * 娴嬭瘯绌烘牸鍒嗛殧鐨勫悕绉拌浆鎹?
     */
    public function testStudlyCaseConvertsSpaceSeparated(): void
    {
        $this->assertEquals('UserCenter', $this->generator->studlyCase('user center'));
    }

    /**
     * 娴嬭瘯娣峰悎鍒嗛殧绗︾殑鍚嶇О杞崲
     */
    public function testStudlyCaseConvertsMixedSeparators(): void
    {
        $this->assertEquals('UserCenterAdmin', $this->generator->studlyCase('user-center_admin'));
    }

    /**
     * 娴嬭瘯宸茬粡鏄?StudlyCase 鐨勫悕绉颁繚鎸佷笉鍙?
     */
    public function testStudlyCasePreservesAlreadyStudly(): void
    {
        $this->assertEquals('UserCenter', $this->generator->studlyCase('UserCenter'));
    }

    /**
     * 娴嬭瘯鍏ㄥぇ鍐欒緭鍏?
     */
    public function testStudlyCaseHandlesUpperCase(): void
    {
        $this->assertEquals('User', $this->generator->studlyCase('USER'));
    }

    /**
     * 娴嬭瘯鍗曚釜鍗曡瘝
     */
    public function testStudlyCaseHandlesSingleWord(): void
    {
        $this->assertEquals('Blog', $this->generator->studlyCase('blog'));
    }

    /**
     * 娴嬭瘯鍖呭惈鏁板瓧鐨勫悕绉?
     */
    public function testStudlyCaseHandlesNumbers(): void
    {
        $this->assertEquals('Module2Test', $this->generator->studlyCase('module2-test'));
    }

    /**
     * 娴嬭瘯杈撳嚭浠ュぇ鍐欏瓧姣嶅紑澶?
     */
    public function testStudlyCaseOutputStartsWithUppercase(): void
    {
        $result = $this->generator->studlyCase('my-module');
        $this->assertMatchesRegularExpression('/^[A-Z]/', $result);
    }

    /**
     * 娴嬭瘯杈撳嚭浠呭寘鍚瓧姣嶅拰鏁板瓧
     */
    public function testStudlyCaseOutputOnlyAlphanumeric(): void
    {
        $result = $this->generator->studlyCase('user-center_admin test');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $result);
    }

    // ==================== moduleExists 娴嬭瘯 ====================

    /**
     * 娴嬭瘯妯″潡瀛樺湪杩斿洖 true
     */
    public function testModuleExistsReturnsTrueWhenExists(): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        mkdir($modulePath, 0755, true);

        $this->assertTrue($this->generator->moduleExists('UserCenter'));
    }

    /**
     * 娴嬭瘯妯″潡涓嶅瓨鍦ㄨ繑鍥?false
     */
    public function testModuleExistsReturnsFalseWhenNotExists(): void
    {
        $this->assertFalse($this->generator->moduleExists('NonExistent'));
    }

    // ==================== getModulePath 娴嬭瘯 ====================

    /**
     * 娴嬭瘯鑾峰彇妯″潡璺緞鏍煎紡姝ｇ‘
     */
    public function testGetModulePathReturnsCorrectPath(): void
    {
        $path = $this->generator->getModulePath('UserCenter');
        $expected = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        $this->assertEquals($expected, $path);
    }

    // ==================== createModule 娴嬭瘯 ====================

    /**
     * 娴嬭瘯鏍囧噯妯″紡鍒涘缓妯″潡鐩綍缁撴瀯
     */
    public function testCreateModuleCreatesStandardDirectories(): void
    {
        $result = $this->generator->createModule('user-center');

        $this->assertTrue($result);

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'controller');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'model');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'service');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'validate');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'middleware');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'event');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'listener');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'command');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'config');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'route');
    }

    /**
     * 娴嬭瘯鏍囧噯妯″紡鐢熸垚 module.json
     */
    public function testCreateModuleGeneratesModuleJson(): void
    {
        $this->generator->createModule('blog');

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');

        $content = file_get_contents($modulePath . DIRECTORY_SEPARATOR . 'module.json');
        $this->assertStringContainsString('Blog', $content);
    }

    /**
     * 娴嬭瘯鏍囧噯妯″紡鐢熸垚绀轰緥鏂囦欢
     */
    public function testCreateModuleGeneratesSampleFiles(): void
    {
        $this->generator->createModule('blog');

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php');
    }

    /**
     * 娴嬭瘯 plain 妯″紡涓嶇敓鎴愮ず渚嬫枃浠?
     */
    public function testCreateModulePlainModeOnlyCreatesDirectories(): void
    {
        $this->generator->createModule('blog', ['plain' => true]);

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';

        // 鐩綍搴旇瀛樺湪
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'controller');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'model');

        // module.json 濮嬬粓鐢熸垚
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');

        // 绀轰緥鏂囦欢涓嶅簲瀛樺湪
        $configFile = $modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $routeFile = $modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php';
        $controllerFile = $modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php';
        $this->assertFileDoesNotExist($configFile);
        $this->assertFileDoesNotExist($routeFile);
        $this->assertFileDoesNotExist($controllerFile);
    }

    /**
     * 娴嬭瘯鍚屽悕妯″潡宸插瓨鍦ㄦ椂杩斿洖 false
     */
    public function testCreateModuleReturnsFalseWhenAlreadyExists(): void
    {
        // 鍏堟墜鍔ㄥ垱寤虹洰褰?
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        mkdir($modulePath, 0755, true);

        $result = $this->generator->createModule('blog');

        $this->assertFalse($result);
    }

    /**
     * 娴嬭瘯鍚嶇О鑷姩杞崲涓?StudlyCase
     */
    public function testCreateModuleConvertsNameToStudlyCase(): void
    {
        $this->generator->createModule('user-center');

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        $this->assertDirectoryExists($modulePath);
    }

    // ==================== generateResource 娴嬭瘯 ====================

    /**
     * 娴嬭瘯鐢熸垚鎺у埗鍣ㄨ祫婧?
     */
    public function testGenerateResourceController(): void
    {
        // 鍏堝垱寤烘ā鍧?
        $this->generator->createModule('blog');

        $filePath = $this->generator->generateResource('blog', 'controller', 'User');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('controller', $filePath);
        $this->assertStringContainsString('User.php', $filePath);

        // 妫€鏌ュ懡鍚嶇┖闂?
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('app\\Blog\\controller', $content);
        $this->assertStringContainsString('User', $content);
    }

    /**
     * 娴嬭瘯鐢熸垚妯″瀷璧勬簮
     */
    public function testGenerateResourceModel(): void
    {
        $this->generator->createModule('blog');

        $filePath = $this->generator->generateResource('blog', 'model', 'Post');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('model', $filePath);
        $this->assertStringContainsString('Post.php', $filePath);
    }

    /**
     * 娴嬭瘯鐢熸垚杩佺Щ璧勬簮锛堝甫鏃堕棿鎴冲墠缂€锛?
     */
    public function testGenerateResourceMigrationWithTimestamp(): void
    {
        $this->generator->createModule('blog');

        $filePath = $this->generator->generateResource('blog', 'migration', 'create_posts');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        // 杩佺Щ鏂囦欢鍚嶅簲鍖呭惈鏃堕棿鎴冲拰琛ㄥ悕
        $filename = basename($filePath);
        $this->assertMatchesRegularExpression('/^\d{14}_create_create_posts_table\.php$/', $filename);
    }

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂杩斿洖绌哄瓧绗︿覆
     */
    public function testGenerateResourceReturnsEmptyWhenModuleNotExists(): void
    {
        $filePath = $this->generator->generateResource('NonExistent', 'controller', 'Test');

        $this->assertEmpty($filePath);
    }

    /**
     * 娴嬭瘯鏃犳晥璧勬簮绫诲瀷杩斿洖绌哄瓧绗︿覆
     */
    public function testGenerateResourceReturnsEmptyForInvalidType(): void
    {
        $this->generator->createModule('blog');

        $filePath = $this->generator->generateResource('blog', 'invalid_type', 'Test');

        $this->assertEmpty($filePath);
    }

    /**
     * 娴嬭瘯鐢熸垚鍛戒护璧勬簮
     */
    public function testGenerateResourceCommand(): void
    {
        $this->generator->createModule('blog');

        $filePath = $this->generator->generateResource('blog', 'command', 'sync-data');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('app\\Blog\\command', $content);
        $this->assertStringContainsString('SyncData', $content);
    }

    // ==================== 杈呭姪鏂规硶 ====================

    /**
     * 鍒涘缓鍩虹 Stub 鏂囦欢
     */
    private function createStubFiles(): void
    {
        // module.json.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'module.json.stub',
            '{"name": "{{MODULE_NAME}}", "alias": "{{LOWER_NAME}}", "enabled": true}'
        );

        // config.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'config.stub',
            "<?php\n// {{MODULE_NAME}} config\nreturn [];\n"
        );

        // route.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'route.stub',
            "<?php\nuse think\\facade\\Route;\nRoute::group('{{LOWER_NAME}}', function () {});\n"
        );

        // controller.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // controller.plain.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.plain.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // model.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'model.stub',
            "<?php\nnamespace {{NAMESPACE}};\nuse think\\Model;\nclass {{CLASS_NAME}} extends Model {\n    protected \$table = '{{TABLE_NAME}}';\n}\n"
        );

        // service.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'service.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // migration.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'migration.stub',
            "<?php\nuse think\\migration\\Migrator;\nclass {{CLASS_NAME}} extends Migrator {\n    public function up(): void { \$this->table('{{TABLE_NAME}}')->create(); }\n}\n"
        );

        // seeder.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'seeder.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // validate.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'validate.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // middleware.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'middleware.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // event.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'event.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // listener.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'listener.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // command.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'command.stub',
            "<?php\nnamespace {{NAMESPACE}};\nuse think\\console\\Command;\nclass {{CLASS_NAME}} extends Command {\n    protected function configure(): void { \$this->setName('{{LOWER_NAME}}:{{TABLE_NAME}}'); }\n}\n"
        );
    }

    /**
     * 鍒涘缓鍙祴璇曠殑 StubResolver 瀹炰緥
     */
    private function createStubResolver(): StubResolver
    {
        return new class($this->packageStubDir, $this->customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };
    }

    /**
     * 鍒涘缓鍙祴璇曠殑 ModuleGenerator 瀹炰緥锛堣鐩?getModulePath 浣跨敤涓存椂鐩綍锛?
     */
    private function createGenerator(StubResolver $stubResolver): ModuleGenerator
    {
        $tempDir = $this->tempDir;
        return new class($stubResolver, $tempDir) extends ModuleGenerator {
            private string $rootPath;

            public function __construct(StubResolver $stubResolver, string $rootPath)
            {
                parent::__construct($stubResolver);
                $this->rootPath = $rootPath . DIRECTORY_SEPARATOR;
            }

            public function getModulePath(string $module): string
            {
                return $this->rootPath . 'app' . DIRECTORY_SEPARATOR . $module;
            }
        };
    }

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

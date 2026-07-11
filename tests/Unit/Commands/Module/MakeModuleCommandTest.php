<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\MakeModuleCommand;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * MakeModuleCommand 鍗曞厓娴嬭瘯
 *
 * 鐢变簬 MakeModuleCommand 鏄?ModuleGenerator 鐨勮杽鍖呰灞傦紝
 * 鏈祴璇曢噸鐐归獙璇佸懡浠ら厤缃纭€у拰閫氳繃鐢熸垚鍣ㄧ殑闆嗘垚琛屼负銆?
 *
 * Requirements: 1.1, 1.2, 1.3, 1.4, 1.6
 */
class MakeModuleCommandTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;
    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        // 鍒涘缓涓存椂鐩綍妯℃嫙椤圭洰缁撴瀯
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'make_module_cmd_test_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'app', 0755, true);

        // 鍒涘缓鍩虹 stub 妯℃澘
        $this->createStubFiles();

        // 浣跨敤鍙祴璇曠殑 StubResolver 鍜?ModuleGenerator
        $stubResolver = $this->createStubResolver();
        $this->generator = $this->createGenerator($stubResolver);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== 鍛戒护閰嶇疆娴嬭瘯 ====================

    /**
     * 娴嬭瘯鍛戒护鍚嶇О閰嶇疆姝ｇ‘
     */
    public function testCommandNameIsCorrect(): void
    {
        $command = new MakeModuleCommand();
        $this->assertEquals('thinkrix:module-make', $command->getName());
    }

    /**
     * 娴嬭瘯鍛戒护鎻忚堪宸查厤缃?
     */
    public function testCommandHasDescription(): void
    {
        $command = new MakeModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 娴嬭瘯鍛戒护瀹氫箟浜?name 鍙傛暟
     */
    public function testCommandHasNameArgument(): void
    {
        $command = new MakeModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('name'));
        $argument = $definition->getArgument('name');
        $this->assertTrue($argument->isRequired());
    }

    /**
     * 娴嬭瘯鍛戒护瀹氫箟浜?--plain 閫夐」
     */
    public function testCommandHasPlainOption(): void
    {
        $command = new MakeModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('plain'));
    }

    /**
     * 娴嬭瘯鍛戒护瀹氫箟浜?--title 閫夐」
     */
    public function testCommandHasTitleOption(): void
    {
        $command = new MakeModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('title'));
    }

    // ==================== 鏍囧噯妯″紡鐩綍缁撴瀯鐢熸垚娴嬭瘯 ====================

    /**
     * 娴嬭瘯鏍囧噯妯″紡鍒涘缓瀹屾暣鐩綍缁撴瀯锛堟ā鎷熷懡浠ゆ墽琛岄€昏緫锛?
     *
     * 楠岃瘉閫氳繃 ModuleGenerator::createModule() 鐢熸垚鐨勬爣鍑嗙洰褰曠粨鏋勫寘鍚細
     * controller, model, service, config, database/migrations, database/seeders, route, module.json
     *
     * 娉ㄦ剰锛歝reateModule 鍐呴儴浼氳皟鐢?studlyCase锛屾墍浠ョ洿鎺ヤ紶鍏ュ師濮嬪悕绉板嵆鍙?
     *
     * Requirements: 1.1, 1.2
     */
    public function testStandardModeCreatesFullDirectoryStructure(): void
    {
        $result = $this->generator->createModule('user-center', [
            'plain' => false,
            'title' => 'UserCenter',
        ]);

        $this->assertTrue($result);

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';

        // 楠岃瘉鏍囧噯鐩綍缁撴瀯
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'controller');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'model');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'service');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'config');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'route');

        // 楠岃瘉 module.json 鏂囦欢瀛樺湪
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');

        // 楠岃瘉鏍囧噯妯″紡涓嬬殑绀轰緥鏂囦欢瀛樺湪
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php');
    }

    // ==================== --plain 閫夐」鏈€灏忕粨鏋勬祴璇?====================

    /**
     * 娴嬭瘯 --plain 閫夐」浠呯敓鎴愭渶灏忕粨鏋勶紙鐩綍 + module.json锛屼笉鍚ず渚嬫枃浠讹級
     *
     * Requirements: 1.6
     */
    public function testPlainModeCreatesMinimalStructure(): void
    {
        $inputName = 'blog';
        $moduleName = $this->generator->studlyCase($inputName);

        $result = $this->generator->createModule($moduleName, [
            'plain' => true,
            'title' => $moduleName,
        ]);

        $this->assertTrue($result);

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';

        // 鐩綍缁撴瀯浠嶇劧瀛樺湪
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'controller');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'model');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'service');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'config');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'route');

        // module.json 濮嬬粓瀛樺湪
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');

        // 绀轰緥鏂囦欢涓嶅簲瀛樺湪
        $this->assertFileDoesNotExist($modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');
        $this->assertFileDoesNotExist($modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php');
        $this->assertFileDoesNotExist($modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php');
    }

    /**
     * 娴嬭瘯 --plain 妯″紡涓?module.json 鍐呭姝ｇ‘
     *
     * 浣跨敤鍘熷杈撳叆鍚嶇О浼犲叆 createModule锛堝唴閮ㄤ細鑷姩杞崲涓?StudlyCase锛?
     *
     * Requirements: 1.6
     */
    public function testPlainModeModuleJsonContainsModuleName(): void
    {
        // 浣跨敤甯﹀垎闅旂鐨勫師濮嬪悕绉帮紝createModule 鍐呴儴浼氳浆涓?StudlyCase
        $this->generator->createModule('test-module', ['plain' => true]);

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'TestModule';
        $content = file_get_contents($modulePath . DIRECTORY_SEPARATOR . 'module.json');

        $this->assertStringContainsString('TestModule', $content);
        $this->assertStringContainsString('testmodule', $content);
    }

    // ==================== 鍚屽悕妯″潡宸插瓨鍦ㄦ椂鐨勯敊璇鐞嗘祴璇?====================

    /**
     * 娴嬭瘯鍚屽悕妯″潡宸插瓨鍦ㄦ椂 createModule 杩斿洖 false
     *
     * 鍦ㄥ懡浠や腑姝よ繑鍥炲€间細瑙﹀彂閿欒杈撳嚭骞惰繑鍥為€€鍑虹爜 1
     *
     * Requirements: 1.4
     */
    public function testModuleAlreadyExistsReturnsFalse(): void
    {
        $moduleName = 'ExistingModule';

        // 棰勫垱寤烘ā鍧楃洰褰?
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        // moduleExists 搴旇繑鍥?true
        $this->assertTrue($this->generator->moduleExists($moduleName));

        // createModule 搴旇繑鍥?false
        $result = $this->generator->createModule($moduleName);
        $this->assertFalse($result);
    }

    /**
     * 娴嬭瘯鍚屽悕妯″潡瀛樺湪鏃朵笉浼氫慨鏀圭幇鏈夌洰褰曞唴瀹?
     *
     * Requirements: 1.4
     */
    public function testModuleAlreadyExistsDoesNotModifyExistingDirectory(): void
    {
        $moduleName = 'ExistingModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        // 鍦ㄦā鍧楃洰褰曚腑鍒涘缓涓€涓爣璁版枃浠?
        $markerFile = $modulePath . DIRECTORY_SEPARATOR . 'marker.txt';
        file_put_contents($markerFile, 'original content');

        // 灏濊瘯鍒涘缓鍚屽悕妯″潡
        $this->generator->createModule($moduleName);

        // 鏍囪鏂囦欢搴斾繚鎸佷笉鍙?
        $this->assertFileExists($markerFile);
        $this->assertEquals('original content', file_get_contents($markerFile));

        // 涓嶅簲鐢熸垚 module.json锛堝洜涓烘搷浣滆缁堟锛?
        $this->assertFileDoesNotExist($modulePath . DIRECTORY_SEPARATOR . 'module.json');
    }

    /**
     * 娴嬭瘯鍛戒护鎵ц娴佺▼涓ā鍧楀瓨鍦ㄦ娴嬩娇鐢?StudlyCase 鍚嶇О
     *
     * Requirements: 1.3, 1.4
     */
    public function testModuleExistsCheckUsesStudlyCaseName(): void
    {
        // 鍒涘缓 UserCenter 鐩綍
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        mkdir($modulePath, 0755, true);

        // 妯℃嫙鍛戒护娴佺▼锛氬厛杞崲鍚嶇О锛屽啀妫€鏌ュ瓨鍦ㄦ€?
        $studlyName = $this->generator->studlyCase('user-center');
        $this->assertEquals('UserCenter', $studlyName);
        $this->assertTrue($this->generator->moduleExists($studlyName));

        // 鍒涘缓搴斿け璐?
        $result = $this->generator->createModule($studlyName);
        $this->assertFalse($result);
    }

    // ==================== 鍚嶇О杞崲娴嬭瘯锛堝懡浠ょ壒瀹氬満鏅級 ====================

    /**
     * 娴嬭瘯 kebab-case 杈撳叆锛坲ser-center 鈫?UserCenter锛?
     *
     * Requirements: 1.3
     */
    public function testNameConversionKebabCase(): void
    {
        $inputName = 'user-center';
        $moduleName = $this->generator->studlyCase($inputName);

        $this->assertEquals('UserCenter', $moduleName);

        // 楠岃瘉鐢熸垚鐨勭洰褰曚娇鐢ㄨ浆鎹㈠悗鐨勫悕绉?
        $this->generator->createModule($moduleName);
        $this->assertDirectoryExists(
            $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter'
        );
    }

    /**
     * 娴嬭瘯 snake_case 杈撳叆锛坲ser_center 鈫?UserCenter锛?
     *
     * Requirements: 1.3
     */
    public function testNameConversionSnakeCase(): void
    {
        $inputName = 'user_center';
        $moduleName = $this->generator->studlyCase($inputName);

        $this->assertEquals('UserCenter', $moduleName);

        $this->generator->createModule($moduleName);
        $this->assertDirectoryExists(
            $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter'
        );
    }

    /**
     * 娴嬭瘯绌烘牸鍒嗛殧杈撳叆锛坲ser center 鈫?UserCenter锛?
     *
     * Requirements: 1.3
     */
    public function testNameConversionSpaceSeparated(): void
    {
        $inputName = 'user center';
        $moduleName = $this->generator->studlyCase($inputName);

        $this->assertEquals('UserCenter', $moduleName);
    }

    /**
     * 娴嬭瘯鍗曚釜鍗曡瘝杈撳叆锛坆log 鈫?Blog锛?
     *
     * Requirements: 1.3
     */
    public function testNameConversionSingleWord(): void
    {
        $inputName = 'blog';
        $moduleName = $this->generator->studlyCase($inputName);

        $this->assertEquals('Blog', $moduleName);

        $this->generator->createModule($moduleName);
        $this->assertDirectoryExists(
            $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog'
        );
    }

    /**
     * 娴嬭瘯澶氭杩炲瓧绗﹁緭鍏ワ紙my-awesome-module 鈫?MyAwesomeModule锛?
     *
     * Requirements: 1.3
     */
    public function testNameConversionMultipleSegments(): void
    {
        $inputName = 'my-awesome-module';
        $moduleName = $this->generator->studlyCase($inputName);

        $this->assertEquals('MyAwesomeModule', $moduleName);
    }

    /**
     * 娴嬭瘯鍖呭惈鏁板瓧鐨勫悕绉帮紙module2-test 鈫?Module2Test锛?
     *
     * Requirements: 1.3
     */
    public function testNameConversionWithNumbers(): void
    {
        $inputName = 'module2-test';
        $moduleName = $this->generator->studlyCase($inputName);

        $this->assertEquals('Module2Test', $moduleName);
    }

    // ==================== 鍛戒护鎵ц娴佺▼闆嗘垚娴嬭瘯 ====================

    /**
     * 娴嬭瘯瀹屾暣鐨勫懡浠ゆ墽琛屾祦绋嬶紙妯℃嫙锛夛細杈撳叆鍚嶇О 鈫?StudlyCase 鈫?妫€鏌?鈫?鍒涘缓
     *
     * 娉ㄦ剰锛歝reateModule 鍐呴儴浼氬啀娆¤皟鐢?studlyCase锛屾墍浠ヤ紶鍏ュ師濮嬪悕绉板嵆鍙?
     *
     * Requirements: 1.1, 1.3
     */
    public function testCommandExecutionFlowWithNameConversion(): void
    {
        // 妯℃嫙鍛戒护鎺ユ敹 'user-center' 杈撳叆
        $inputName = 'user-center';

        // 姝ラ 1锛氳浆鎹㈠悕绉帮紙鍛戒护灞傝涓猴級
        $moduleName = $this->generator->studlyCase($inputName);
        $this->assertEquals('UserCenter', $moduleName);

        // 姝ラ 2锛氭鏌ユā鍧楁槸鍚﹀瓨鍦?
        $this->assertFalse($this->generator->moduleExists($moduleName));

        // 姝ラ 3锛氫娇鐢ㄥ師濮嬪悕绉板垱寤烘ā鍧楋紙createModule 鍐呴儴浼氭墽琛?studlyCase锛?
        $result = $this->generator->createModule($inputName, [
            'plain' => false,
            'title' => $moduleName,
        ]);
        $this->assertTrue($result);

        // 姝ラ 4锛氶獙璇佹ā鍧楃幇鍦ㄥ瓨鍦紙浣跨敤 StudlyCase 鍚嶇О妫€鏌ワ級
        $this->assertTrue($this->generator->moduleExists($moduleName));
    }

    /**
     * 娴嬭瘯 module.json 鍖呭惈姝ｇ‘鐨勬ā鍧楀悕绉帮紙鏍囧噯妯″紡锛?
     *
     * Requirements: 1.1, 1.2
     */
    public function testModuleJsonContainsCorrectNameInStandardMode(): void
    {
        $this->generator->createModule('Blog', [
            'plain' => false,
            'title' => 'Blog',
        ]);

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog';
        $content = file_get_contents($modulePath . DIRECTORY_SEPARATOR . 'module.json');
        $json = json_decode($content, true);

        $this->assertNotNull($json);
        $this->assertEquals('Blog', $json['name']);
        $this->assertEquals('blog', $json['alias']);
    }

    /**
     * 娴嬭瘯鏍囧噯妯″紡涓嬫帶鍒跺櫒鍛藉悕绌洪棿姝ｇ‘
     *
     * Requirements: 1.2
     */
    public function testStandardModeControllerHasCorrectNamespace(): void
    {
        // 浣跨敤甯﹀垎闅旂鐨勫師濮嬪悕绉帮紝createModule 鍐呴儴浼氳浆涓?StudlyCase
        $this->generator->createModule('user-center', [
            'plain' => false,
            'title' => 'UserCenter',
        ]);

        $controllerPath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR
            . 'UserCenter' . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php';

        $this->assertFileExists($controllerPath);

        $content = file_get_contents($controllerPath);
        $this->assertStringContainsString('app\\UserCenter\\controller', $content);
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
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // service.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'service.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // migration.stub
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'migration.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
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
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
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

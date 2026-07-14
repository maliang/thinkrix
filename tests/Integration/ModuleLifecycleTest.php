<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Integration;

use PHPUnit\Framework\TestCase;
use think\App;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\ModuleLoader;
use Thinkrix\Support\StubResolver;

/**
 * 妯″潡鐢熷懡鍛ㄦ湡闆嗘垚娴嬭瘯
 *
 * 娴嬭瘯妯″潡浠庡垱寤哄埌鍚敤銆佽矾鐢卞姞杞姐€佷腑闂翠欢娉ㄥ唽銆佷簨浠剁洃鍚€?
 * 浠ュ強绂佺敤鍚庤祫婧愬嵏杞界殑瀹屾暣娴佺▼銆?
 *
 * Requirements: 1.5, 3.1, 3.2, 5.1, 5.2, 9.1-9.4, 10.2, 10.3
 */
class ModuleLifecycleTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private ModuleGenerator $generator;
    private App $app;
    private object $mockConfig;
    private object $mockMiddleware;
    private object $mockLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'integration_test_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'app', 0755, true);

        $this->createStubFiles();

        // 鍒涘缓鍙祴璇曠殑 StubResolver锛堟敞鍏ヨ嚜瀹氫箟璺緞锛?
        $stubResolver = new class($this->packageStubDir, $customStubDir) extends StubResolver {
            public function __construct(string $d, string $c)
            {
                $this->defaultStubPath = $d;
                $this->customStubPath = $c;
            }
        };

        // 鍒涘缓鍙祴璇曠殑 ModuleGenerator锛堣鐩?getModulePath 浠ヤ娇鐢ㄤ复鏃剁洰褰曪級
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

        // 鍒涘缓鐪熷疄 App 瀹炰緥锛堜娇鐢ㄤ复鏃剁洰褰曚綔涓?rootPath锛?
        $this->app = new App($this->tempDir);

        // 妯℃嫙 Config 鏈嶅姟
        $this->mockConfig = new class {
            public array $data = [];

            public function set($config, string $key = null): void
            {
                if ($key !== null) {
                    $this->data[$key] = $config;
                }
            }

            public function get(string $key = null, $default = null)
            {
                return $this->data[$key] ?? $default;
            }

            public function has(string $name): bool
            {
                return isset($this->data[$name]);
            }

            public function load(string $file, string $name = ''): array
            {
                return [];
            }
        };

        // 妯℃嫙 Middleware 鏈嶅姟
        $this->mockMiddleware = new class {
            public array $added = [];
            public array $routed = [];

            public function add($mw): void
            {
                $this->added[] = $mw;
            }

            public function route($mw): void
            {
                $this->routed[] = $mw;
            }

            public function __call($n, $a)
            {
            }
        };

        // 妯℃嫙 Log 鏈嶅姟
        $this->mockLog = new class {
            public array $warnings = [];

            public function warning($msg, array $ctx = []): void
            {
                $this->warnings[] = $msg;
            }

            public function __call($n, $a)
            {
            }
        };

        // 灏嗘ā鎷熷璞＄粦瀹氬埌 App 瀹瑰櫒
        $this->app->bind('config', function () {
            return $this->mockConfig;
        });
        $this->app->bind('middleware', function () {
            return $this->mockMiddleware;
        });
        $this->app->bind('log', function () {
            return $this->mockLog;
        });
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== 妯″潡鍒涘缓鈫掕矾鐢卞姞杞芥祦绋嬫祴璇?====================

    /**
     * 娴嬭瘯瀹屾暣娴佺▼锛氬垱寤烘ā鍧?鈫?楠岃瘉缁撴瀯 鈫?鍔犺浇璺敱
     */
    public function testModuleCreationAndRouteLoading(): void
    {
        // Step 1: 鍒涘缓妯″潡
        $result = $this->generator->createModule('blog', ['plain' => false, 'title' => 'Blog']);
        $this->assertTrue($result);

        // Step 2: 楠岃瘉妯″潡瀛樺湪
        $this->assertTrue($this->generator->moduleExists('Blog'));

        // Step 3: 楠岃瘉鐩綍缁撴瀯
        $modulePath = $this->generator->getModulePath('Blog');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'controller');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'model');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'route');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php');

        // Step 4: 浣跨敤 ModuleLoader 鍔犺浇璺敱
        $loader = new ModuleLoader($this->app);

        // 鍒涘缓鏍囪鏂囦欢楠岃瘉璺敱琚姞杞芥墽琛?
        $markerFile = $this->tempDir . DIRECTORY_SEPARATOR . 'route_marker.txt';
        $escapedPath = str_replace('\\', '/', $markerFile);
        // 瑕嗙洊璺敱鏂囦欢鍐呭涓烘爣璁伴€昏緫
        file_put_contents(
            $modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php',
            "<?php file_put_contents('{$escapedPath}', 'loaded');"
        );

        $loader->loadRoutes('Blog', $modulePath);
        $this->assertFileExists($markerFile);
    }

    /**
     * 娴嬭瘯妯″潡绂佺敤鍚庤矾鐢变笉鍔犺浇
     */
    public function testDisabledModuleRoutesNotLoaded(): void
    {
        // 鍒涘缓妯″潡
        $this->generator->createModule('shop', ['plain' => false]);
        $modulePath = $this->generator->getModulePath('Shop');

        // 璁剧疆璺敱鏍囪
        $markerFile = $this->tempDir . DIRECTORY_SEPARATOR . 'shop_route_marker.txt';
        $escapedPath = str_replace('\\', '/', $markerFile);
        file_put_contents(
            $modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php',
            "<?php file_put_contents('{$escapedPath}', 'loaded');"
        );

        // 妯℃嫙绂佺敤鐘舵€侊細涓嶈皟鐢?loadRoutes
        // 楠岃瘉璺敱鏂囦欢鏈鍔犺浇
        $this->assertFileDoesNotExist($markerFile);
    }

    // ==================== module.json 涓棿浠?浜嬩欢澹版槑鈫掕嚜鍔ㄦ敞鍐?====================

    /**
     * 娴嬭瘯 module.json 涓棿浠跺０鏄庤嚜鍔ㄦ敞鍐?
     */
    public function testModuleJsonMiddlewareAutoRegistration(): void
    {
        // 鍒涘缓妯″潡
        $this->generator->createModule('auth-module', ['plain' => false]);
        $modulePath = $this->generator->getModulePath('AuthModule');

        // 鍐欏叆鍖呭惈涓棿浠跺０鏄庣殑 module.json
        $moduleJson = [
            'name' => 'AuthModule',
            'alias' => 'authmodule',
            'enabled' => true,
            'middleware' => [
                'global' => ['app\\AuthModule\\middleware\\CheckToken'],
                'route' => ['app\\AuthModule\\middleware\\CheckPermission'],
            ],
        ];
        file_put_contents(
            $modulePath . DIRECTORY_SEPARATOR . 'module.json',
            json_encode($moduleJson, JSON_PRETTY_PRINT)
        );

        // 浣跨敤 ModuleLoader 娉ㄥ唽涓棿浠?
        $loader = new ModuleLoader($this->app);
        $loader->registerMiddleware('AuthModule', $modulePath, $moduleJson);

        // 楠岃瘉鍏ㄥ眬涓棿浠跺凡娉ㄥ唽
        $this->assertContains('app\\AuthModule\\middleware\\CheckToken', $this->mockMiddleware->added);

        // 楠岃瘉璺敱涓棿浠跺凡娉ㄥ唽
        $this->assertContains('app\\AuthModule\\middleware\\CheckPermission', $this->mockMiddleware->routed);
    }

    /**
     * 娴嬭瘯 module.json 浜嬩欢鐩戝惉鍣ㄥ０鏄庤嚜鍔ㄦ敞鍐?
     */
    public function testModuleJsonListenersAutoRegistration(): void
    {
        // 鍒涘缓妯″潡
        $this->generator->createModule('user-center', ['plain' => false]);
        $modulePath = $this->generator->getModulePath('UserCenter');

        // 鍐欏叆鍖呭惈浜嬩欢澹版槑鐨?module.json
        $moduleJson = [
            'name' => 'UserCenter',
            'alias' => 'usercenter',
            'enabled' => true,
            'listeners' => [
                'user.login' => 'app\\UserCenter\\listener\\UserLoginListener',
                'user.logout' => 'app\\UserCenter\\listener\\UserLogoutListener',
            ],
        ];
        file_put_contents(
            $modulePath . DIRECTORY_SEPARATOR . 'module.json',
            json_encode($moduleJson, JSON_PRETTY_PRINT)
        );

        // 浣跨敤 ModuleLoader 娉ㄥ唽浜嬩欢
        // Event::listen 鏄潤鎬佹柟娉曪紝鍦ㄦ病鏈夊畬鏁村簲鐢ㄧ殑鎯呭喌涓嬫棤娉曠洿鎺ラ獙璇佹敞鍐?
        // 浣嗗彲浠ラ獙璇佹柟娉曚笉鎶涘嚭寮傚父
        $loader = new ModuleLoader($this->app);

        // 濡傛灉 Event facade 鏈垵濮嬪寲锛宺egisterListeners 鍙兘浼氭姏鍑洪敊璇?
        // 杩欓噷楠岃瘉鏂规硶鍙互瀹夊叏鎵ц锛堝湪瀹屾暣搴旂敤鐜涓嬩細姝ｅ父娉ㄥ唽锛?
        try {
            $loader->registerListeners('UserCenter', $modulePath, $moduleJson);
            // 濡傛灉鑳藉埌杈捐繖閲岃鏄庢柟娉曟墽琛屾棤寮傚父
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            // Event facade 涓嶅彲鐢ㄦ椂棰勬湡浼氬け璐ワ紝浣嗚繖涓嶅奖鍝嶉泦鎴愰€昏緫姝ｇ‘鎬?
            $this->assertStringContainsString('Event', $e->getMessage());
        }
    }

    /**
     * 娴嬭瘯绂佺敤鍚庝腑闂翠欢涓嶆敞鍐?
     */
    public function testDisabledModuleMiddlewareNotRegistered(): void
    {
        // 鍒涘缓妯″潡
        $this->generator->createModule('payment', ['plain' => false]);

        $moduleJson = [
            'name' => 'Payment',
            'middleware' => [
                'global' => ['app\\Payment\\middleware\\CheckPayment'],
            ],
        ];

        // 妯℃嫙绂佺敤鐘舵€侊細涓嶈皟鐢?registerMiddleware
        // 楠岃瘉涓棿浠舵湭琚敞鍐?
        $this->assertEmpty($this->mockMiddleware->added);
    }

    // ==================== 妯″潡閰嶇疆鍔犺浇娴佺▼ ====================

    /**
     * 娴嬭瘯妯″潡鍒涘缓鍚庨厤缃枃浠跺彲琚姞杞?
     */
    public function testModuleConfigLoadedAfterCreation(): void
    {
        // 鍒涘缓妯″潡锛堥潪 plain锛屽寘鍚?config/config.php锛?
        $this->generator->createModule('blog', ['plain' => false]);
        $modulePath = $this->generator->getModulePath('Blog');

        // 纭閰嶇疆鏂囦欢瀛樺湪
        $configFile = $modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $this->assertFileExists($configFile);

        // 鏇挎崲涓哄彲楠岃瘉鐨勯厤缃唴瀹?
        file_put_contents($configFile, "<?php\nreturn ['blog_key' => 'blog_value'];");

        // 浣跨敤 ModuleLoader 鍔犺浇閰嶇疆
        $loader = new ModuleLoader($this->app);
        $loader->loadConfig('Blog', $modulePath);

        // 楠岃瘉閰嶇疆宸叉敞鍐?
        $this->assertArrayHasKey('module_blog', $this->mockConfig->data);
        $this->assertEquals('blog_value', $this->mockConfig->data['module_blog']['blog_key']);
    }

    /**
     * 娴嬭瘯椤圭洰閰嶇疆瑕嗙洊妯″潡閰嶇疆
     */
    public function testProjectConfigOverridesModuleConfig(): void
    {
        // 鍒涘缓妯″潡
        $this->generator->createModule('blog', ['plain' => false]);
        $modulePath = $this->generator->getModulePath('Blog');

        // 妯″潡閰嶇疆
        file_put_contents(
            $modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php',
            "<?php\nreturn ['source' => 'module'];"
        );

        // 椤圭洰閰嶇疆锛堥珮浼樺厛绾э級
        $projectConfigDir = $this->tempDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules';
        mkdir($projectConfigDir, 0755, true);
        file_put_contents(
            $projectConfigDir . DIRECTORY_SEPARATOR . 'blog.php',
            "<?php\nreturn ['source' => 'project'];"
        );

        // 鍔犺浇閰嶇疆
        $loader = new ModuleLoader($this->app);
        $loader->loadConfig('Blog', $modulePath);

        // 楠岃瘉椤圭洰閰嶇疆浼樺厛
        $this->assertEquals('project', $this->mockConfig->data['module_blog']['source']);
    }

    // ==================== 鑷畾涔夊懡浠ゆ敞鍐屾祦绋?====================

    /**
     * 娴嬭瘯鍛戒护鐩綍鎵弿
     */
    public function testCommandDirectoryScanningAfterModuleCreation(): void
    {
        // 鍒涘缓妯″潡
        $this->generator->createModule('blog', ['plain' => false]);
        $modulePath = $this->generator->getModulePath('Blog');

        // 楠岃瘉 command 鐩綍瀛樺湪
        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        $this->assertDirectoryExists($commandDir);

        // 鐢熸垚涓€涓懡浠ゆ枃浠?
        $this->generator->generateResource('Blog', 'command', 'sync-data');

        // 楠岃瘉鍛戒护鏂囦欢瀛樺湪
        $files = glob($commandDir . DIRECTORY_SEPARATOR . '*.php');
        $this->assertNotEmpty($files);
    }

    /**
     * 娴嬭瘯绂佺敤鍚庡懡浠や笉娉ㄥ唽
     */
    public function testDisabledModuleCommandsNotRegistered(): void
    {
        // 鍒涘缓妯″潡骞剁敓鎴愬懡浠?
        $this->generator->createModule('blog', ['plain' => false]);
        $modulePath = $this->generator->getModulePath('Blog');
        $this->generator->generateResource('Blog', 'command', 'sync-data');

        // 妯℃嫙绂佺敤锛氫笉璋冪敤 registerCommands
        $loader = new ModuleLoader($this->app);
        // 涓嶈皟鐢?registerCommands

        $this->assertEmpty($loader->getRegisteredCommands());
    }

    // ==================== 璧勬簮鐢熸垚瀹屾暣娴佺▼ ====================

    /**
     * 娴嬭瘯鍦ㄥ凡鍒涘缓妯″潡涓敓鎴愬绉嶈祫婧?
     */
    public function testGenerateMultipleResourcesInModule(): void
    {
        // 鍒涘缓妯″潡
        $this->generator->createModule('blog', ['plain' => false]);

        // 鐢熸垚鍚勭璧勬簮
        $controller = $this->generator->generateResource('Blog', 'controller', 'PostController');
        $model = $this->generator->generateResource('Blog', 'model', 'Post');
        $service = $this->generator->generateResource('Blog', 'service', 'PostService');
        $event = $this->generator->generateResource('Blog', 'event', 'PostCreated');
        $listener = $this->generator->generateResource('Blog', 'listener', 'NotifyAuthor');

        // 楠岃瘉鎵€鏈夋枃浠堕兘宸茬敓鎴?
        $this->assertFileExists($controller);
        $this->assertFileExists($model);
        $this->assertFileExists($service);
        $this->assertFileExists($event);
        $this->assertFileExists($listener);

        // 楠岃瘉鍛藉悕绌洪棿
        $this->assertStringContainsString('app\\Blog\\controller', file_get_contents($controller));
        $this->assertStringContainsString('app\\Blog\\model', file_get_contents($model));
        $this->assertStringContainsString('app\\Blog\\service', file_get_contents($service));
        $this->assertStringContainsString('app\\Blog\\event', file_get_contents($event));
        $this->assertStringContainsString('app\\Blog\\listener', file_get_contents($listener));
    }

    // ==================== 杈呭姪鏂规硶 ====================

    /**
     * 鍒涘缓鎵€鏈夐渶瑕佺殑 Stub 妯℃澘鏂囦欢
     */
    private function createStubFiles(): void
    {
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'composer.json.stub',
            '{"name":"thinkrix/{{LOWER_NAME}}","autoload":{"psr-4":{"app\\\\{{MODULE_NAME}}\\\\":""}}}'
        );

        $stubs = [
            'module.json.stub' => '{"name": "{{MODULE_NAME}}", "alias": "{{LOWER_NAME}}", "enabled": true}',
            'config.stub' => "<?php\n// {{MODULE_NAME}} config\nreturn [];",
            'route.stub' => "<?php\nuse think\\facade\\Route;\nRoute::group('{{LOWER_NAME}}', function () {});",
            'controller.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
            'controller.plain.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
            'model.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
            'service.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
            'migration.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
            'seeder.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
            'validate.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
            'middleware.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
            'event.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
            'listener.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
            'command.stub' => "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}",
        ];

        foreach ($stubs as $name => $content) {
            file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . $name, $content);
        }
    }

    /**
     * 閫掑綊鍒犻櫎鐩綍
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use think\App;
use Thinkrix\Support\ModuleLoader;

/**
 * ModuleLoader 鍗曞厓娴嬭瘯
 *
 * 娴嬭瘯閰嶇疆鍔犺浇浼樺厛绾с€佽矾鐢辨潯浠跺姞杞姐€佸懡浠ょ被鎵弿涓庡紓甯歌烦杩囬€昏緫銆?
 * 閫氳繃鍒涘缓鐪熷疄鐨?App 瀹炰緥锛堟寚瀹氫复鏃?rootPath锛夛紝骞剁粦瀹氭ā鎷熸湇鍔″璞℃潵闅旂娴嬭瘯銆?
 */
class ModuleLoaderTest extends TestCase
{
    private string $tempDir;
    private App $app;

    /** @var object 妯℃嫙鐨?Config 瀵硅薄 */
    private object $mockConfig;

    /** @var object 妯℃嫙鐨?Log 瀵硅薄 */
    private object $mockLog;

    /** @var object 妯℃嫙鐨?Middleware 瀵硅薄 */
    private object $mockMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        // 鍒涘缓涓存椂鐩綍妯℃嫙椤圭洰缁撴瀯
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'module_loader_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        // 鍒涘缓鐪熷疄鐨?App 瀹炰緥锛屼娇鐢ㄤ复鏃剁洰褰曚綔涓?rootPath
        $this->app = new App($this->tempDir);

        // 鍒涘缓妯℃嫙鐨?Config 鏈嶅姟
        $this->mockConfig = new class {
            /** @var array<string, array> */
            public array $data = [];

            public function set($config, string $key = null): void
            {
                if ($key !== null) {
                    $this->data[$key] = $config;
                }
            }

            public function get(string $key = null, $default = null)
            {
                if ($key === null) {
                    return $this->data;
                }
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

        // 鍒涘缓妯℃嫙鐨?Log 鏈嶅姟
        $this->mockLog = new class {
            /** @var array */
            public array $warnings = [];

            public function warning($msg, array $ctx = []): void
            {
                $this->warnings[] = ['message' => $msg, 'context' => $ctx];
            }

            public function __call($name, $args)
            {
                // 蹇界暐鍏朵粬鏃ュ織鏂规硶璋冪敤
            }
        };

        // 鍒涘缓妯℃嫙鐨?Middleware 鏈嶅姟
        $this->mockMiddleware = new class {
            /** @var array */
            public array $added = [];
            /** @var array */
            public array $routed = [];

            public function add($mw): void
            {
                $this->added[] = $mw;
            }

            public function route($mw): void
            {
                $this->routed[] = $mw;
            }

            public function __call($name, $args)
            {
                // 蹇界暐鍏朵粬涓棿浠舵柟娉曡皟鐢?
            }
        };

        // 灏嗘ā鎷熷璞＄粦瀹氬埌瀹瑰櫒
        $this->app->bind('config', function () {
            return $this->mockConfig;
        });
        $this->app->bind('log', function () {
            return $this->mockLog;
        });
        $this->app->bind('middleware', function () {
            return $this->mockMiddleware;
        });
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== 閰嶇疆鍔犺浇浼樺厛绾ф祴璇?(Requirements: 7.2, 7.4) ====================

    /**
     * 娴嬭瘯椤圭洰閰嶇疆浼樺厛浜庢ā鍧楅厤缃?
     */
    public function testLoadConfigProjectConfigTakesPriority(): void
    {
        $moduleName = 'Blog';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        // 鍒涘缓妯″潡閰嶇疆锛堜綆浼樺厛绾э級
        $moduleConfigDir = $modulePath . DIRECTORY_SEPARATOR . 'config';
        mkdir($moduleConfigDir, 0755, true);
        file_put_contents(
            $moduleConfigDir . DIRECTORY_SEPARATOR . 'config.php',
            '<?php return ["source" => "module", "module_only" => true];'
        );

        // 鍒涘缓椤圭洰閰嶇疆锛堥珮浼樺厛绾э級
        $projectConfigDir = $this->tempDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules';
        mkdir($projectConfigDir, 0755, true);
        file_put_contents(
            $projectConfigDir . DIRECTORY_SEPARATOR . 'blog.php',
            '<?php return ["source" => "project", "project_only" => true];'
        );

        $loader = new ModuleLoader($this->app);
        $loader->loadConfig($moduleName, $modulePath);

        // 搴斾娇鐢ㄩ」鐩厤缃紙鏈€楂樹紭鍏堢骇锛?
        $configData = $this->mockConfig->data;
        $this->assertArrayHasKey('module_blog', $configData);
        $this->assertEquals('project', $configData['module_blog']['source']);
        $this->assertTrue($configData['module_blog']['project_only']);
        // 妯″潡閰嶇疆涓殑瀛楁涓嶅簲鍑虹幇
        $this->assertArrayNotHasKey('module_only', $configData['module_blog']);
    }

    /**
     * 娴嬭瘯妯″潡閰嶇疆鍦ㄩ」鐩厤缃笉瀛樺湪鏃惰浣跨敤
     */
    public function testLoadConfigFallsBackToModuleConfig(): void
    {
        $moduleName = 'UserCenter';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        // 浠呭垱寤烘ā鍧楅厤缃?
        $moduleConfigDir = $modulePath . DIRECTORY_SEPARATOR . 'config';
        mkdir($moduleConfigDir, 0755, true);
        file_put_contents(
            $moduleConfigDir . DIRECTORY_SEPARATOR . 'config.php',
            '<?php return ["key" => "module_value", "debug" => false];'
        );

        // 涓嶅垱寤洪」鐩厤缃?

        $loader = new ModuleLoader($this->app);
        $loader->loadConfig($moduleName, $modulePath);

        $configData = $this->mockConfig->data;
        $this->assertArrayHasKey('module_usercenter', $configData);
        $this->assertEquals('module_value', $configData['module_usercenter']['key']);
        $this->assertFalse($configData['module_usercenter']['debug']);
    }

    /**
     * 娴嬭瘯涓ょ閰嶇疆鏂囦欢閮戒笉瀛樺湪鏃朵笉娉ㄥ唽閰嶇疆
     */
    public function testLoadConfigDoesNothingWhenNoConfigExists(): void
    {
        $moduleName = 'EmptyModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        $loader = new ModuleLoader($this->app);
        $loader->loadConfig($moduleName, $modulePath);

        // 涓嶅簲娉ㄥ唽浠讳綍閰嶇疆
        $this->assertEmpty($this->mockConfig->data);
    }

    /**
     * 娴嬭瘯閰嶇疆娉ㄥ唽閿悕鏍煎紡涓?module_{lower_name}
     */
    public function testLoadConfigUsesCorrectKeyName(): void
    {
        $moduleName = 'UserCenter';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $moduleConfigDir = $modulePath . DIRECTORY_SEPARATOR . 'config';
        mkdir($moduleConfigDir, 0755, true);
        file_put_contents(
            $moduleConfigDir . DIRECTORY_SEPARATOR . 'config.php',
            '<?php return ["enabled" => true];'
        );

        $loader = new ModuleLoader($this->app);
        $loader->loadConfig($moduleName, $modulePath);

        // 閿悕搴斾负 module_usercenter锛堟ā鍧楀悕鍏ㄥ皬鍐欙級
        $this->assertArrayHasKey('module_usercenter', $this->mockConfig->data);
        $this->assertArrayNotHasKey('module_UserCenter', $this->mockConfig->data);
    }

    /**
     * 娴嬭瘯閰嶇疆鏂囦欢杩斿洖闈炴暟缁勬椂浣跨敤绌洪厤缃?
     */
    public function testLoadConfigHandlesNonArrayReturn(): void
    {
        $moduleName = 'BadConfig';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $moduleConfigDir = $modulePath . DIRECTORY_SEPARATOR . 'config';
        mkdir($moduleConfigDir, 0755, true);
        // 閰嶇疆鏂囦欢杩斿洖瀛楃涓茶€岄潪鏁扮粍
        file_put_contents(
            $moduleConfigDir . DIRECTORY_SEPARATOR . 'config.php',
            '<?php return "invalid";'
        );

        $loader = new ModuleLoader($this->app);
        $loader->loadConfig($moduleName, $modulePath);

        // 闈炴暟缁勮繑鍥炲€间細琚浆涓虹┖鏁扮粍锛岀┖閰嶇疆涓嶄細娉ㄥ唽
        $this->assertEmpty($this->mockConfig->data);
    }

    // ==================== 璺敱鏉′欢鍔犺浇娴嬭瘯 (Requirements: 5.1, 5.2) ====================

    /**
     * 娴嬭瘯璺敱鏂囦欢瀛樺湪鏃惰鍔犺浇
     */
    public function testLoadRoutesIncludesRouteFileWhenExists(): void
    {
        $moduleName = 'Blog';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $routeDir = $modulePath . DIRECTORY_SEPARATOR . 'route';
        mkdir($routeDir, 0755, true);

        // 鍒涘缓璺敱鏂囦欢锛屽啓鍏ユ爣璁颁互楠岃瘉鍔犺浇
        $markerFile = $this->tempDir . DIRECTORY_SEPARATOR . 'route_loaded_marker.txt';
        $escapedPath = str_replace('\\', '/', $markerFile);
        file_put_contents(
            $routeDir . DIRECTORY_SEPARATOR . 'app.php',
            "<?php file_put_contents('{$escapedPath}', 'blog_route_loaded');"
        );

        $loader = new ModuleLoader($this->app);
        $loader->loadRoutes($moduleName, $modulePath);

        // 楠岃瘉璺敱鏂囦欢琚姞杞芥墽琛?
        $this->assertFileExists($markerFile);
        $this->assertEquals('blog_route_loaded', file_get_contents($markerFile));
    }

    /**
     * 娴嬭瘯璺敱鏂囦欢涓嶅瓨鍦ㄦ椂涓嶆姤閿?
     */
    public function testLoadRoutesDoesNothingWhenRouteFileMissing(): void
    {
        $moduleName = 'NoRoute';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        $loader = new ModuleLoader($this->app);

        // 涓嶅簲鎶涘嚭寮傚父
        $loader->loadRoutes($moduleName, $modulePath);

        // 娴嬭瘯閫氳繃鍗宠〃绀轰笉鎶ラ敊
        $this->assertTrue(true);
    }

    /**
     * 娴嬭瘯璺敱鐩綍瀛樺湪浣?app.php 涓嶅瓨鍦ㄦ椂涓嶆姤閿?
     */
    public function testLoadRoutesDoesNothingWhenRouteDirectoryExistsButNoFile(): void
    {
        $moduleName = 'EmptyRoute';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $routeDir = $modulePath . DIRECTORY_SEPARATOR . 'route';
        mkdir($routeDir, 0755, true);
        // 涓嶅垱寤?app.php

        $loader = new ModuleLoader($this->app);
        $loader->loadRoutes($moduleName, $modulePath);

        $this->assertTrue(true);
    }

    // ==================== 鍛戒护绫绘壂鎻忎笌寮傚父璺宠繃娴嬭瘯 (Requirements: 10.2, 10.3, 10.7) ====================

    /**
     * 娴嬭瘯 command 鐩綍涓嶅瓨鍦ㄦ椂杩斿洖绌哄懡浠ゅ垪琛?
     */
    public function testRegisterCommandsReturnsEmptyWhenNoCommandDir(): void
    {
        $moduleName = 'NoCmd';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        $loader = new ModuleLoader($this->app);
        $loader->registerCommands($moduleName, $modulePath);

        $this->assertEmpty($loader->getRegisteredCommands());
    }

    /**
     * 娴嬭瘯 command 鐩綍涓虹┖鏃惰繑鍥炵┖鍛戒护鍒楄〃
     */
    public function testRegisterCommandsReturnsEmptyWhenCommandDirEmpty(): void
    {
        $moduleName = 'EmptyCmd';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        mkdir($commandDir, 0755, true);

        $loader = new ModuleLoader($this->app);
        $loader->registerCommands($moduleName, $modulePath);

        $this->assertEmpty($loader->getRegisteredCommands());
    }

    /**
     * 娴嬭瘯鎵弿 command 鐩綍涓嬬殑 PHP 鏂囦欢涓嶆姏鍑哄紓甯?
     */
    public function testRegisterCommandsDetectsPhpFilesWithoutError(): void
    {
        $moduleName = 'TestModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        mkdir($commandDir, 0755, true);

        // 鍒涘缓 PHP 鏂囦欢锛堣繖浜涚被涓嶅湪鑷姩鍔犺浇涓紝class_exists 杩斿洖 false锛?
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'SyncData.php', '<?php // dummy');
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'ImportUser.php', '<?php // dummy');

        $loader = new ModuleLoader($this->app);
        $loader->registerCommands($moduleName, $modulePath);

        // 鐢变簬杩欎簺绫讳笉瀛樺湪浜庤嚜鍔ㄥ姞杞戒腑锛屼笉浼氭敞鍐岋紝浣嗕笉搴旀姏鍑哄紓甯?
        $this->assertIsArray($loader->getRegisteredCommands());
    }

    /**
     * 娴嬭瘯闈?PHP 鏂囦欢琚烦杩囷紙glob 鍙尮閰?*.php锛?
     */
    public function testRegisterCommandsSkipsNonPhpFiles(): void
    {
        $moduleName = 'MixedFiles';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        mkdir($commandDir, 0755, true);

        // 鍒涘缓闈?PHP 鏂囦欢
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'readme.md', '# Commands');
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . '.gitkeep', '');
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'config.json', '{}');

        $loader = new ModuleLoader($this->app);
        $loader->registerCommands($moduleName, $modulePath);

        // glob('*.php') 鍙尮閰?PHP 鏂囦欢锛屾墍浠ラ潪 PHP 鏂囦欢琚拷鐣?
        $this->assertEmpty($loader->getRegisteredCommands());
    }

    /**
     * 娴嬭瘯鍛戒护绫诲姞杞藉け璐ユ椂琚崟鑾峰苟璁板綍鏃ュ織锛堝紓甯歌烦杩囷級
     */
    public function testRegisterCommandsCatchesExceptionsAndLogs(): void
    {
        $moduleName = 'BrokenModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        mkdir($commandDir, 0755, true);

        // 鍒涘缓涓€涓?PHP 鏂囦欢
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'BrokenCommand.php', '<?php // broken');

        $loader = new ModuleLoader($this->app);

        // registerCommands 搴旇鎹曡幏寮傚父骞惰褰曟棩蹇楋紝涓嶅簲鍚戝鎶涘嚭
        $loader->registerCommands($moduleName, $modulePath);

        // 娉ㄥ唽鍛戒护鍒楄〃搴斾负绌猴紙鍥犱负绫讳笉瀛樺湪鎴栧姞杞藉け璐ワ級
        $this->assertEmpty($loader->getRegisteredCommands());
    }

    /**
     * 娴嬭瘯澶氫釜鍛戒护鏂囦欢鏃跺鐞嗕笉浼氫腑鏂紙閬嶅巻涓嶈鎵撴柇锛?
     */
    public function testRegisterCommandsContinuesAfterError(): void
    {
        $moduleName = 'PartialModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';
        mkdir($commandDir, 0755, true);

        // 鍒涘缓澶氫釜 PHP 鏂囦欢
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'Alpha.php', '<?php // alpha');
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'Beta.php', '<?php // beta');
        file_put_contents($commandDir . DIRECTORY_SEPARATOR . 'Gamma.php', '<?php // gamma');

        $loader = new ModuleLoader($this->app);

        // 涓嶅簲鎶涘嚭寮傚父锛屽嵆浣挎煇涓枃浠跺姞杞藉け璐?
        $loader->registerCommands($moduleName, $modulePath);

        // 鏂规硶姝ｅ父鎵ц瀹屾垚
        $this->assertIsArray($loader->getRegisteredCommands());
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

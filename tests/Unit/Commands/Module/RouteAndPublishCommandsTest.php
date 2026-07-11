<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\RouteListCommand;
use Thinkrix\Commands\Module\PublishStubsCommand;
use Thinkrix\Commands\Module\PublishConfigCommand;
use Thinkrix\Commands\Module\BaseModuleCommand;
use Thinkrix\Support\StubResolver;
use ReflectionMethod;

/**
 * 璺敱绠＄悊涓庨厤缃彂甯冨懡浠ゅ崟鍏冩祴璇?
 *
 * 娴嬭瘯璺敱鍒楄〃杈撳嚭銆丼tub 鍙戝竷鍒版纭洰褰曘€侀厤缃彂甯冧笌鏂囦欢澶嶅埗銆?
 *
 * Requirements: 5.4, 6.1, 7.3
 */
class RouteAndPublishCommandsTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;

    protected function setUp(): void
    {
        parent::setUp();

        // 鍒涘缓涓存椂鐩綍妯℃嫙椤圭洰缁撴瀯
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'route_publish_test_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== RouteListCommand 閰嶇疆娴嬭瘯 ====================

    /**
     * 娴嬭瘯 RouteListCommand 鍛戒护鍚嶇О姝ｇ‘
     *
     * Requirements: 5.4
     */
    public function testRouteListCommandName(): void
    {
        $command = new RouteListCommand();
        $this->assertEquals('thinkrix:module-route-list', $command->getName());
    }

    /**
     * 娴嬭瘯 RouteListCommand 鏈夊繀闇€鐨?module 鍙傛暟
     *
     * Requirements: 5.4
     */
    public function testRouteListCommandHasModuleArgument(): void
    {
        $command = new RouteListCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('module'));
        $this->assertTrue($definition->getArgument('module')->isRequired());
    }

    /**
     * 娴嬭瘯 RouteListCommand 鏈夋弿杩颁俊鎭?
     *
     * Requirements: 5.4
     */
    public function testRouteListCommandHasDescription(): void
    {
        $command = new RouteListCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 娴嬭瘯 RouteListCommand 缁ф壙 BaseModuleCommand
     *
     * Requirements: 5.4
     */
    public function testRouteListCommandExtendsBaseModuleCommand(): void
    {
        $command = new RouteListCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    /**
     * 娴嬭瘯 parseRouteDefinitions 瑙ｆ瀽 Route::get 妯″紡
     *
     * Requirements: 5.4
     */
    public function testParseRouteDefinitionsMatchesGetRoute(): void
    {
        $command = new RouteListCommand();
        $method = new ReflectionMethod($command, 'parseRouteDefinitions');
        $method->setAccessible(true);

        $content = "Route::get('users', 'UserController@index');";
        $result = $method->invoke($command, $content);

        $this->assertCount(1, $result);
        $this->assertEquals(['GET', 'users', 'UserController@index'], $result[0]);
    }

    /**
     * 娴嬭瘯 parseRouteDefinitions 瑙ｆ瀽 Route::post 妯″紡
     *
     * Requirements: 5.4
     */
    public function testParseRouteDefinitionsMatchesPostRoute(): void
    {
        $command = new RouteListCommand();
        $method = new ReflectionMethod($command, 'parseRouteDefinitions');
        $method->setAccessible(true);

        $content = "Route::post('users/create', 'UserController@store');";
        $result = $method->invoke($command, $content);

        $this->assertCount(1, $result);
        $this->assertEquals(['POST', 'users/create', 'UserController@store'], $result[0]);
    }

    /**
     * 娴嬭瘯 parseRouteDefinitions 瑙ｆ瀽澶氱 HTTP 鏂规硶
     *
     * Requirements: 5.4
     */
    public function testParseRouteDefinitionsMatchesMultipleMethods(): void
    {
        $command = new RouteListCommand();
        $method = new ReflectionMethod($command, 'parseRouteDefinitions');
        $method->setAccessible(true);

        $content = <<<'PHP'
Route::get('users', 'UserController@index');
Route::post('users', 'UserController@store');
Route::put('users/:id', 'UserController@update');
Route::delete('users/:id', 'UserController@destroy');
PHP;

        $result = $method->invoke($command, $content);

        $this->assertCount(4, $result);
        $this->assertEquals('GET', $result[0][0]);
        $this->assertEquals('POST', $result[1][0]);
        $this->assertEquals('PUT', $result[2][0]);
        $this->assertEquals('DELETE', $result[3][0]);
    }

    /**
     * 娴嬭瘯 parseRouteDefinitions 瀵?group 妯″紡杩斿洖绌烘暟缁?
     *
     * Requirements: 5.4
     */
    public function testParseRouteDefinitionsReturnsEmptyForGroupPatterns(): void
    {
        $command = new RouteListCommand();
        $method = new ReflectionMethod($command, 'parseRouteDefinitions');
        $method->setAccessible(true);

        $content = <<<'PHP'
Route::group('api', function () {
    // 宓屽璺敱
});
PHP;

        $result = $method->invoke($command, $content);

        $this->assertEmpty($result);
    }

    /**
     * 娴嬭瘯 parseRouteDefinitions 瀵圭┖鍐呭杩斿洖绌烘暟缁?
     *
     * Requirements: 5.4
     */
    public function testParseRouteDefinitionsReturnsEmptyForEmptyContent(): void
    {
        $command = new RouteListCommand();
        $method = new ReflectionMethod($command, 'parseRouteDefinitions');
        $method->setAccessible(true);

        $result = $method->invoke($command, '');

        $this->assertEmpty($result);
    }

    // ==================== PublishStubsCommand 閰嶇疆娴嬭瘯 ====================

    /**
     * 娴嬭瘯 PublishStubsCommand 鍛戒护鍚嶇О姝ｇ‘
     *
     * Requirements: 6.1
     */
    public function testPublishStubsCommandName(): void
    {
        $command = new PublishStubsCommand();
        $this->assertEquals('thinkrix:module-publish-stubs', $command->getName());
    }

    /**
     * 娴嬭瘯 PublishStubsCommand 鏈夋弿杩颁俊鎭?
     *
     * Requirements: 6.1
     */
    public function testPublishStubsCommandHasDescription(): void
    {
        $command = new PublishStubsCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 娴嬭瘯 PublishStubsCommand 娌℃湁蹇呴渶鍙傛暟
     *
     * Requirements: 6.1
     */
    public function testPublishStubsCommandHasNoRequiredArguments(): void
    {
        $command = new PublishStubsCommand();
        $definition = $command->getDefinition();
        $arguments = $definition->getArguments();

        // 鍛戒护涓嶅簲鏈変换浣曞繀闇€鍙傛暟
        $requiredArguments = array_filter($arguments, fn($arg) => $arg->isRequired());
        $this->assertEmpty($requiredArguments, 'PublishStubsCommand should have no required arguments');
    }

    /**
     * 娴嬭瘯 PublishStubsCommand 缁ф壙 BaseModuleCommand
     *
     * Requirements: 6.1
     */
    public function testPublishStubsCommandExtendsBaseModuleCommand(): void
    {
        $command = new PublishStubsCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    /**
     * 娴嬭瘯 StubResolver::publishStubs 灏嗘枃浠跺鍒跺埌鑷畾涔夌洰褰?
     *
     * Requirements: 6.1
     */
    public function testStubResolverPublishStubsCopiesFilesToCustomDir(): void
    {
        // 鍒涘缓娴嬭瘯 stub 鏂囦欢
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'model.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'service.stub',
            "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {}\n"
        );

        // 鍒涘缓鍙祴璇曠殑 StubResolver 瀹炰緥
        $stubResolver = new class($this->packageStubDir, $this->customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };

        // 鎵ц鍙戝竷
        $published = $stubResolver->publishStubs();

        // 楠岃瘉缁撴灉
        $this->assertNotEmpty($published);
        $this->assertCount(3, $published);

        // 楠岃瘉鏂囦欢纭疄琚鍒跺埌鑷畾涔夌洰褰?
        $this->assertArrayHasKey('controller.stub', $published);
        $this->assertArrayHasKey('model.stub', $published);
        $this->assertArrayHasKey('service.stub', $published);

        // 楠岃瘉鐩爣鏂囦欢瀛樺湪
        foreach ($published as $filename => $targetPath) {
            $this->assertFileExists($targetPath);
            $this->assertStringContainsString($this->customStubDir, $targetPath);
        }
    }

    /**
     * 娴嬭瘯 StubResolver::publishStubs 鍦ㄦ棤 stub 鏂囦欢鏃惰繑鍥炵┖鏁扮粍
     *
     * Requirements: 6.1
     */
    public function testStubResolverPublishStubsReturnsEmptyWhenNoStubs(): void
    {
        // 鍒涘缓涓€涓┖鐨?stub 鐩綍
        $emptyStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'empty_stubs';
        mkdir($emptyStubDir, 0755, true);

        $stubResolver = new class($emptyStubDir, $this->customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };

        $published = $stubResolver->publishStubs();

        $this->assertEmpty($published);
    }

    /**
     * 娴嬭瘯 StubResolver::publishStubs 涓嶈鐩栧凡瀛樺湪鐨勮嚜瀹氫箟鏂囦欢
     *
     * Requirements: 6.1
     */
    public function testStubResolverPublishStubsDoesNotOverwriteExisting(): void
    {
        // 鍒涘缓榛樿 stub 鏂囦欢
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub',
            "default content"
        );

        // 鍒涘缓宸插瓨鍦ㄧ殑鑷畾涔?stub 鏂囦欢
        mkdir($this->customStubDir, 0755, true);
        file_put_contents(
            $this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub',
            "custom content"
        );

        $stubResolver = new class($this->packageStubDir, $this->customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };

        $stubResolver->publishStubs();

        // 楠岃瘉鑷畾涔夋枃浠跺唴瀹规湭琚鐩?
        $content = file_get_contents($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub');
        $this->assertEquals("custom content", $content);
    }

    // ==================== PublishConfigCommand 閰嶇疆娴嬭瘯 ====================

    /**
     * 娴嬭瘯 PublishConfigCommand 鍛戒护鍚嶇О姝ｇ‘
     *
     * Requirements: 7.3
     */
    public function testPublishConfigCommandName(): void
    {
        $command = new PublishConfigCommand();
        $this->assertEquals('thinkrix:module-publish-config', $command->getName());
    }

    /**
     * 娴嬭瘯 PublishConfigCommand 鏈夊繀闇€鐨?module 鍙傛暟
     *
     * Requirements: 7.3
     */
    public function testPublishConfigCommandHasModuleArgument(): void
    {
        $command = new PublishConfigCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('module'));
        $this->assertTrue($definition->getArgument('module')->isRequired());
    }

    /**
     * 娴嬭瘯 PublishConfigCommand 鏈夋弿杩颁俊鎭?
     *
     * Requirements: 7.3
     */
    public function testPublishConfigCommandHasDescription(): void
    {
        $command = new PublishConfigCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 娴嬭瘯 PublishConfigCommand 缁ф壙 BaseModuleCommand
     *
     * Requirements: 7.3
     */
    public function testPublishConfigCommandExtendsBaseModuleCommand(): void
    {
        $command = new PublishConfigCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    /**
     * 娴嬭瘯閰嶇疆鏂囦欢鍙姝ｇ‘璇诲彇涓庡鍒?
     *
     * Requirements: 7.3
     */
    public function testConfigFileCanBeReadAndCopied(): void
    {
        // 妯℃嫙妯″潡閰嶇疆鏂囦欢
        $moduleConfigDir = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Blog' . DIRECTORY_SEPARATOR . 'config';
        mkdir($moduleConfigDir, 0755, true);

        $configContent = <<<'PHP'
<?php
return [
    'name' => 'Blog',
    'version' => '1.0.0',
    'enabled' => true,
];
PHP;

        $sourceFile = $moduleConfigDir . DIRECTORY_SEPARATOR . 'config.php';
        file_put_contents($sourceFile, $configContent);

        // 妯℃嫙鐩爣鐩綍
        $targetDir = $this->tempDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules';
        mkdir($targetDir, 0755, true);

        $targetFile = $targetDir . DIRECTORY_SEPARATOR . 'blog.php';

        // 鎵ц澶嶅埗锛堟ā鎷?PublishConfigCommand 鐨勬牳蹇冮€昏緫锛?
        copy($sourceFile, $targetFile);

        // 楠岃瘉鏂囦欢琚纭鍒?
        $this->assertFileExists($targetFile);

        // 楠岃瘉鍐呭涓€鑷?
        $copiedContent = file_get_contents($targetFile);
        $this->assertEquals($configContent, $copiedContent);

        // 楠岃瘉閰嶇疆鍙姝ｇ‘鍔犺浇
        $config = include $targetFile;
        $this->assertIsArray($config);
        $this->assertEquals('Blog', $config['name']);
        $this->assertEquals('1.0.0', $config['version']);
        $this->assertTrue($config['enabled']);
    }

    /**
     * 娴嬭瘯閰嶇疆鏂囦欢澶嶅埗浼氳鐩栧凡瀛樺湪鐨勬枃浠?
     *
     * Requirements: 7.3
     */
    public function testConfigFileCopyOverwritesExisting(): void
    {
        // 鍒涘缓婧愰厤缃枃浠?
        $sourceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'source';
        mkdir($sourceDir, 0755, true);
        $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . 'config.php';
        file_put_contents($sourceFile, "<?php\nreturn ['version' => '2.0.0'];");

        // 鍒涘缓宸插瓨鍦ㄧ殑鐩爣鏂囦欢
        $targetDir = $this->tempDir . DIRECTORY_SEPARATOR . 'target';
        mkdir($targetDir, 0755, true);
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . 'blog.php';
        file_put_contents($targetFile, "<?php\nreturn ['version' => '1.0.0'];");

        // 鎵ц瑕嗙洊澶嶅埗
        copy($sourceFile, $targetFile);

        // 楠岃瘉鍐呭宸叉洿鏂?
        $config = include $targetFile;
        $this->assertEquals('2.0.0', $config['version']);
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

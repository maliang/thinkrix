<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Thinkrix\Support\StubResolver;

/**
 * StubResolver 鍗曞厓娴嬭瘯
 */
class StubResolverTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;

    protected function setUp(): void
    {
        parent::setUp();

        // 鍒涘缓涓存椂鐩綍妯℃嫙椤圭洰缁撴瀯
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'stub_resolver_test_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'package' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        // 鑷畾涔夌洰褰曚笉涓€瀹氬瓨鍦?
    }

    protected function tearDown(): void
    {
        // 閫掑綊鍒犻櫎涓存椂鐩綍
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * 娴嬭瘯 resolve 鏂规硶鏇挎崲鎵€鏈夊崰浣嶇
     */
    public function testResolveReplacesAllPlaceholders(): void
    {
        // 鍒涘缓娴嬭瘯 Stub 鏂囦欢
        $stubContent = "<?php\nnamespace {{NAMESPACE}};\nclass {{CLASS_NAME}} {\n    // Module: {{MODULE_NAME}}\n}";
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'test.stub', $stubContent);

        $resolver = $this->createResolver();

        $result = $resolver->resolve('test.stub', [
            '{{NAMESPACE}}' => 'app\\UserCenter\\controller',
            '{{CLASS_NAME}}' => 'UserController',
            '{{MODULE_NAME}}' => 'UserCenter',
        ]);

        $this->assertStringContainsString('namespace app\\UserCenter\\controller;', $result);
        $this->assertStringContainsString('class UserController', $result);
        $this->assertStringContainsString('Module: UserCenter', $result);
        $this->assertStringNotContainsString('{{NAMESPACE}}', $result);
        $this->assertStringNotContainsString('{{CLASS_NAME}}', $result);
        $this->assertStringNotContainsString('{{MODULE_NAME}}', $result);
    }

    /**
     * 娴嬭瘯 resolve 鏂规硶鏀寔鎵€鏈夋爣鍑嗗崰浣嶇
     */
    public function testResolveSupportsAllStandardPlaceholders(): void
    {
        $stubContent = "{{MODULE_NAME}} {{LOWER_NAME}} {{NAMESPACE}} {{CLASS_NAME}} {{TABLE_NAME}} {{TIMESTAMP}}";
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'full.stub', $stubContent);

        $resolver = $this->createResolver();

        $result = $resolver->resolve('full.stub', [
            '{{MODULE_NAME}}' => 'UserCenter',
            '{{LOWER_NAME}}' => 'usercenter',
            '{{NAMESPACE}}' => 'app\\UserCenter\\model',
            '{{CLASS_NAME}}' => 'User',
            '{{TABLE_NAME}}' => 'user_center_users',
            '{{TIMESTAMP}}' => '20240101120000',
        ]);

        $this->assertEquals('UserCenter usercenter app\\UserCenter\\model User user_center_users 20240101120000', $result);
    }

    /**
     * 娴嬭瘯 getStubPath 浼樺厛杩斿洖鑷畾涔夎矾寰?
     */
    public function testGetStubPathPrefersCustomPath(): void
    {
        // 鍒涘缓涓や釜鍚屽悕鏂囦欢
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub', 'default');
        mkdir($this->customStubDir, 0755, true);
        file_put_contents($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub', 'custom');

        $resolver = $this->createResolver();

        $path = $resolver->getStubPath('controller.stub');

        $this->assertEquals($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub', $path);
    }

    /**
     * 娴嬭瘯 getStubPath 鑷畾涔変笉瀛樺湪鏃跺洖閫€榛樿
     */
    public function testGetStubPathFallsBackToDefault(): void
    {
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'model.stub', 'default model');

        $resolver = $this->createResolver();

        $path = $resolver->getStubPath('model.stub');

        $this->assertEquals($this->packageStubDir . DIRECTORY_SEPARATOR . 'model.stub', $path);
    }

    /**
     * 娴嬭瘯 resolve 褰撴枃浠朵笉瀛樺湪鏃惰繑鍥炵┖瀛楃涓插苟瑙﹀彂璀﹀憡
     */
    public function testResolveReturnsEmptyStringWhenStubMissing(): void
    {
        $resolver = $this->createResolver();

        // 鏈熸湜瑙﹀彂 E_USER_WARNING
        $warningTriggered = false;
        set_error_handler(function ($errno) use (&$warningTriggered) {
            if ($errno === E_USER_WARNING) {
                $warningTriggered = true;
            }
            return true;
        });

        $result = $resolver->resolve('nonexistent.stub', ['{{MODULE_NAME}}' => 'Test']);

        restore_error_handler();

        $this->assertEmpty($result);
        $this->assertTrue($warningTriggered, '搴斿綋瑙﹀彂 E_USER_WARNING');
    }

    /**
     * 娴嬭瘯 publishStubs 鏂规硶灏嗘枃浠跺鍒跺埌鐩爣鐩綍
     */
    public function testPublishStubsCopiesFiles(): void
    {
        // 鍒涘缓鍑犱釜榛樿 Stub 鏂囦欢
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub', 'controller content');
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'model.stub', 'model content');

        $resolver = $this->createResolver();
        $published = $resolver->publishStubs();

        $this->assertCount(2, $published);
        $this->assertArrayHasKey('controller.stub', $published);
        $this->assertArrayHasKey('model.stub', $published);

        // 楠岃瘉鏂囦欢纭疄琚鍒?
        $this->assertFileExists($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub');
        $this->assertFileExists($this->customStubDir . DIRECTORY_SEPARATOR . 'model.stub');
        $this->assertEquals('controller content', file_get_contents($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub'));
    }

    /**
     * 娴嬭瘯 publishStubs 涓嶈鐩栧凡瀛樺湪鐨勬枃浠?
     */
    public function testPublishStubsDoesNotOverwriteExisting(): void
    {
        file_put_contents($this->packageStubDir . DIRECTORY_SEPARATOR . 'controller.stub', 'default');
        mkdir($this->customStubDir, 0755, true);
        file_put_contents($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub', 'customized');

        $resolver = $this->createResolver();
        $resolver->publishStubs();

        // 宸插瓨鍦ㄧ殑鏂囦欢涓嶅簲琚鐩?
        $this->assertEquals('customized', file_get_contents($this->customStubDir . DIRECTORY_SEPARATOR . 'controller.stub'));
    }

    /**
     * 娴嬭瘯 getPlaceholders 杩斿洖鎵€鏈夋敮鎸佺殑鍗犱綅绗?
     */
    public function testGetPlaceholdersReturnsExpectedKeys(): void
    {
        $resolver = $this->createResolver();
        $placeholders = $resolver->getPlaceholders();

        $this->assertArrayHasKey('{{MODULE_NAME}}', $placeholders);
        $this->assertArrayHasKey('{{LOWER_NAME}}', $placeholders);
        $this->assertArrayHasKey('{{NAMESPACE}}', $placeholders);
        $this->assertArrayHasKey('{{CLASS_NAME}}', $placeholders);
        $this->assertArrayHasKey('{{TABLE_NAME}}', $placeholders);
        $this->assertArrayHasKey('{{TIMESTAMP}}', $placeholders);
    }

    /**
     * 鍒涘缓鍙祴璇曠殑 StubResolver 瀹炰緥锛堟敞鍏ユ祴璇曠洰褰曡矾寰勶級
     */
    private function createResolver(): StubResolver
    {
        $resolver = new class($this->packageStubDir, $this->customStubDir) extends StubResolver {
            public function __construct(string $defaultPath, string $customPath)
            {
                $this->defaultStubPath = $defaultPath;
                $this->customStubPath = $customPath;
            }
        };

        return $resolver;
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

<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\MakeControllerCommand;
use Thinkrix\Commands\Module\MakeModelCommand;
use Thinkrix\Commands\Module\MakeServiceCommand;
use Thinkrix\Commands\Module\MakeMigrationCommand;
use Thinkrix\Commands\Module\MakeSeederCommand;
use Thinkrix\Commands\Module\MakeValidateCommand;
use Thinkrix\Commands\Module\MakeMiddlewareCommand;
use Thinkrix\Commands\Module\MakeEventCommand;
use Thinkrix\Commands\Module\MakeListenerCommand;
use Thinkrix\Commands\Module\MakeCommandCommand;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * 璧勬簮鐢熸垚鍛戒护鍗曞厓娴嬭瘯
 *
 * 娴嬭瘯鎵€鏈夋ā鍧楀唴璧勬簮鐢熸垚鍛戒护鐨勯厤缃纭€с€佹枃浠剁敓鎴愪笌鍛藉悕绌洪棿璁剧疆銆?
 * 浣跨敤涓存椂鐩綍妯℃嫙椤圭洰缁撴瀯锛岄伩鍏嶄緷璧?ThinkPHP app() 瀹瑰櫒銆?
 *
 * Requirements: 2.1-2.11
 */
class ResourceCommandsTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;
    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        // 鍒涘缓涓存椂鐩綍妯℃嫙椤圭洰缁撴瀯
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'resource_cmd_test_' . uniqid();
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
     * 娴嬭瘯 MakeControllerCommand 鍛戒护閰嶇疆
     *
     * Requirements: 2.1
     */
    public function testMakeControllerCommandConfiguration(): void
    {
        $command = new MakeControllerCommand();
        $this->assertEquals('thinkrix:module-make-controller', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
        $this->assertTrue($definition->getArgument('name')->isRequired());
        $this->assertTrue($definition->getArgument('module')->isRequired());
    }

    /**
     * 娴嬭瘯 MakeModelCommand 鍛戒护閰嶇疆
     *
     * Requirements: 2.2
     */
    public function testMakeModelCommandConfiguration(): void
    {
        $command = new MakeModelCommand();
        $this->assertEquals('thinkrix:module-make-model', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
        $this->assertTrue($definition->getArgument('name')->isRequired());
        $this->assertTrue($definition->getArgument('module')->isRequired());
    }

    /**
     * 娴嬭瘯 MakeServiceCommand 鍛戒护閰嶇疆
     *
     * Requirements: 2.3
     */
    public function testMakeServiceCommandConfiguration(): void
    {
        $command = new MakeServiceCommand();
        $this->assertEquals('thinkrix:module-make-service', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 娴嬭瘯 MakeMigrationCommand 鍛戒护閰嶇疆
     *
     * Requirements: 2.4
     */
    public function testMakeMigrationCommandConfiguration(): void
    {
        $command = new MakeMigrationCommand();
        $this->assertEquals('thinkrix:module-make-migration', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 娴嬭瘯 MakeSeederCommand 鍛戒护閰嶇疆
     *
     * Requirements: 2.5
     */
    public function testMakeSeederCommandConfiguration(): void
    {
        $command = new MakeSeederCommand();
        $this->assertEquals('thinkrix:module-make-seeder', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 娴嬭瘯 MakeValidateCommand 鍛戒护閰嶇疆
     *
     * Requirements: 2.6
     */
    public function testMakeValidateCommandConfiguration(): void
    {
        $command = new MakeValidateCommand();
        $this->assertEquals('thinkrix:module-make-validate', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 娴嬭瘯 MakeMiddlewareCommand 鍛戒护閰嶇疆
     *
     * Requirements: 2.7
     */
    public function testMakeMiddlewareCommandConfiguration(): void
    {
        $command = new MakeMiddlewareCommand();
        $this->assertEquals('thinkrix:module-make-middleware', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 娴嬭瘯 MakeEventCommand 鍛戒护閰嶇疆
     *
     * Requirements: 2.8
     */
    public function testMakeEventCommandConfiguration(): void
    {
        $command = new MakeEventCommand();
        $this->assertEquals('thinkrix:module-make-event', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 娴嬭瘯 MakeListenerCommand 鍛戒护閰嶇疆
     *
     * Requirements: 2.9
     */
    public function testMakeListenerCommandConfiguration(): void
    {
        $command = new MakeListenerCommand();
        $this->assertEquals('thinkrix:module-make-listener', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    /**
     * 娴嬭瘯 MakeCommandCommand 鍛戒护閰嶇疆
     *
     * Requirements: 2.10
     */
    public function testMakeCommandCommandConfiguration(): void
    {
        $command = new MakeCommandCommand();
        $this->assertEquals('thinkrix:module-make-command', $command->getName());
        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasArgument('module'));
    }

    // ==================== 鎺у埗鍣ㄧ敓鎴愭祴璇?====================

    /**
     * 娴嬭瘯鎺у埗鍣ㄦ枃浠剁敓鎴愬埌姝ｇ‘鐩綍
     *
     * Requirements: 2.1, 2.11
     */
    public function testGenerateControllerCreatesFileInCorrectDirectory(): void
    {
        // 棰勫垱寤烘ā鍧楃洰褰?
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'controller', 'UserController');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('controller', $filePath);
    }

    /**
     * 娴嬭瘯鎺у埗鍣ㄥ懡鍚嶇┖闂存纭?
     *
     * Requirements: 2.1, 2.11
     */
    public function testGenerateControllerHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 浣跨敤杩炲瓧绗﹀垎闅旂殑鍚嶇О锛宻tudlyCase 浼氭纭浆鎹负 UserController
        $filePath = $this->generator->generateResource('Blog', 'controller', 'user-controller');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\controller', $content);
        $this->assertStringContainsString('UserController', $content);
    }

    // ==================== 妯″瀷鐢熸垚娴嬭瘯 ====================

    /**
     * 娴嬭瘯妯″瀷鏂囦欢鐢熸垚鍒版纭洰褰?
     *
     * Requirements: 2.2, 2.11
     */
    public function testGenerateModelCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'model', 'User');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('model', $filePath);
    }

    /**
     * 娴嬭瘯妯″瀷鍛藉悕绌洪棿姝ｇ‘
     *
     * Requirements: 2.2, 2.11
     */
    public function testGenerateModelHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'model', 'User');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\model', $content);
        $this->assertStringContainsString('User', $content);
    }

    // ==================== 鏈嶅姟鐢熸垚娴嬭瘯 ====================

    /**
     * 娴嬭瘯鏈嶅姟鏂囦欢鐢熸垚鍒版纭洰褰?
     *
     * Requirements: 2.3, 2.11
     */
    public function testGenerateServiceCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'service', 'UserService');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('service', $filePath);
    }

    /**
     * 娴嬭瘯鏈嶅姟鍛藉悕绌洪棿姝ｇ‘
     *
     * Requirements: 2.3, 2.11
     */
    public function testGenerateServiceHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 浣跨敤杩炲瓧绗﹀垎闅旂殑鍚嶇О锛宻tudlyCase 浼氭纭浆鎹负 UserService
        $filePath = $this->generator->generateResource('Blog', 'service', 'user-service');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\service', $content);
        $this->assertStringContainsString('UserService', $content);
    }

    // ==================== 杩佺Щ鐢熸垚娴嬭瘯 ====================

    /**
     * 娴嬭瘯杩佺Щ鏂囦欢鐢熸垚鍒版纭洰褰?
     *
     * Requirements: 2.4, 2.11
     */
    public function testGenerateMigrationCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'migration', 'create_posts');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('database' . DIRECTORY_SEPARATOR . 'migrations', $filePath);
    }

    /**
     * 娴嬭瘯杩佺Щ鏂囦欢鍚嶅寘鍚椂闂存埑鍓嶇紑
     *
     * Requirements: 2.4
     */
    public function testGenerateMigrationFileNameHasTimestampPrefix(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'migration', 'create_posts');
        $filename = basename($filePath);

        // 鏂囦欢鍚嶆牸寮忥細{YmdHis}_create_{table_name}_table.php
        $this->assertMatchesRegularExpression('/^\d{14}_create_/', $filename);
        $this->assertStringEndsWith('.php', $filename);
    }

    /**
     * 娴嬭瘯杩佺Щ鍛藉悕绌洪棿涓?database 灞傜骇
     *
     * Requirements: 2.4, 2.11
     */
    public function testGenerateMigrationHasDatabaseNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'migration', 'create_posts');
        $content = file_get_contents($filePath);

        // migration 鐨勫懡鍚嶇┖闂村簲涓?app\{Module}\database
        $this->assertStringContainsString('app\\Blog\\database', $content);
    }

    // ==================== Seeder 鐢熸垚娴嬭瘯 ====================

    /**
     * 娴嬭瘯 Seeder 鏂囦欢鐢熸垚鍒版纭洰褰?
     *
     * Requirements: 2.5, 2.11
     */
    public function testGenerateSeederCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'seeder', 'UserSeeder');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('database' . DIRECTORY_SEPARATOR . 'seeders', $filePath);
    }

    /**
     * 娴嬭瘯 Seeder 鍛藉悕绌洪棿涓?database 灞傜骇
     *
     * Requirements: 2.5, 2.11
     */
    public function testGenerateSeederHasDatabaseNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'seeder', 'UserSeeder');
        $content = file_get_contents($filePath);

        // seeder 鐨勫懡鍚嶇┖闂村簲涓?app\{Module}\database
        $this->assertStringContainsString('app\\Blog\\database', $content);
    }

    // ==================== 楠岃瘉鍣ㄧ敓鎴愭祴璇?====================

    /**
     * 娴嬭瘯楠岃瘉鍣ㄦ枃浠剁敓鎴愬埌姝ｇ‘鐩綍
     *
     * Requirements: 2.6, 2.11
     */
    public function testGenerateValidateCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'validate', 'UserValidate');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('validate', $filePath);
    }

    /**
     * 娴嬭瘯楠岃瘉鍣ㄥ懡鍚嶇┖闂存纭?
     *
     * Requirements: 2.6, 2.11
     */
    public function testGenerateValidateHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 浣跨敤杩炲瓧绗﹀垎闅旂殑鍚嶇О锛宻tudlyCase 浼氭纭浆鎹负 UserValidate
        $filePath = $this->generator->generateResource('Blog', 'validate', 'user-validate');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\validate', $content);
        $this->assertStringContainsString('UserValidate', $content);
    }

    // ==================== 涓棿浠剁敓鎴愭祴璇?====================

    /**
     * 娴嬭瘯涓棿浠舵枃浠剁敓鎴愬埌姝ｇ‘鐩綍
     *
     * Requirements: 2.7, 2.11
     */
    public function testGenerateMiddlewareCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'middleware', 'CheckAuth');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('middleware', $filePath);
    }

    /**
     * 娴嬭瘯涓棿浠跺懡鍚嶇┖闂存纭?
     *
     * Requirements: 2.7, 2.11
     */
    public function testGenerateMiddlewareHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 浣跨敤杩炲瓧绗﹀垎闅旂殑鍚嶇О锛宻tudlyCase 浼氭纭浆鎹负 CheckAuth
        $filePath = $this->generator->generateResource('Blog', 'middleware', 'check-auth');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\middleware', $content);
        $this->assertStringContainsString('CheckAuth', $content);
    }

    // ==================== 浜嬩欢鐢熸垚娴嬭瘯 ====================

    /**
     * 娴嬭瘯浜嬩欢鏂囦欢鐢熸垚鍒版纭洰褰?
     *
     * Requirements: 2.8, 2.11
     */
    public function testGenerateEventCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'event', 'UserCreated');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('event', $filePath);
    }

    /**
     * 娴嬭瘯浜嬩欢鍛藉悕绌洪棿姝ｇ‘
     *
     * Requirements: 2.8, 2.11
     */
    public function testGenerateEventHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 浣跨敤杩炲瓧绗﹀垎闅旂殑鍚嶇О锛宻tudlyCase 浼氭纭浆鎹负 UserCreated
        $filePath = $this->generator->generateResource('Blog', 'event', 'user-created');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\event', $content);
        $this->assertStringContainsString('UserCreated', $content);
    }

    // ==================== 鐩戝惉鍣ㄧ敓鎴愭祴璇?====================

    /**
     * 娴嬭瘯鐩戝惉鍣ㄦ枃浠剁敓鎴愬埌姝ｇ‘鐩綍
     *
     * Requirements: 2.9, 2.11
     */
    public function testGenerateListenerCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'listener', 'SendNotification');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('listener', $filePath);
    }

    /**
     * 娴嬭瘯鐩戝惉鍣ㄥ懡鍚嶇┖闂存纭?
     *
     * Requirements: 2.9, 2.11
     */
    public function testGenerateListenerHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        // 浣跨敤杩炲瓧绗﹀垎闅旂殑鍚嶇О锛宻tudlyCase 浼氭纭浆鎹负 SendNotification
        $filePath = $this->generator->generateResource('Blog', 'listener', 'send-notification');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\listener', $content);
        $this->assertStringContainsString('SendNotification', $content);
    }

    // ==================== 鍛戒护鏂囦欢鐢熸垚娴嬭瘯 ====================

    /**
     * 娴嬭瘯鍛戒护鏂囦欢鐢熸垚鍒版纭洰褰?
     *
     * Requirements: 2.10, 2.11
     */
    public function testGenerateCommandCreatesFileInCorrectDirectory(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'command', 'sync-data');

        $this->assertNotEmpty($filePath);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('command', $filePath);
    }

    /**
     * 娴嬭瘯鍛戒护鏂囦欢鍛藉悕绌洪棿姝ｇ‘
     *
     * Requirements: 2.10, 2.11
     */
    public function testGenerateCommandHasCorrectNamespace(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'command', 'sync-data');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\Blog\\command', $content);
        // sync-data 缁?studlyCase 杞崲涓?SyncData
        $this->assertStringContainsString('SyncData', $content);
    }

    // ==================== 妯″潡涓嶅瓨鍦ㄦ椂鐨勯敊璇鐞嗘祴璇?====================

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂 generateResource 杩斿洖绌哄瓧绗︿覆锛坈ontroller锛?
     *
     * Requirements: 2.10
     */
    public function testGenerateControllerReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'controller', 'UserController');
        $this->assertEmpty($result);
    }

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂 generateResource 杩斿洖绌哄瓧绗︿覆锛坢odel锛?
     *
     * Requirements: 2.10
     */
    public function testGenerateModelReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'model', 'User');
        $this->assertEmpty($result);
    }

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂 generateResource 杩斿洖绌哄瓧绗︿覆锛坰ervice锛?
     *
     * Requirements: 2.10
     */
    public function testGenerateServiceReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'service', 'UserService');
        $this->assertEmpty($result);
    }

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂 generateResource 杩斿洖绌哄瓧绗︿覆锛坢igration锛?
     *
     * Requirements: 2.10
     */
    public function testGenerateMigrationReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'migration', 'create_posts');
        $this->assertEmpty($result);
    }

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂 generateResource 杩斿洖绌哄瓧绗︿覆锛坰eeder锛?
     *
     * Requirements: 2.10
     */
    public function testGenerateSeederReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'seeder', 'UserSeeder');
        $this->assertEmpty($result);
    }

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂 generateResource 杩斿洖绌哄瓧绗︿覆锛坴alidate锛?
     *
     * Requirements: 2.10
     */
    public function testGenerateValidateReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'validate', 'UserValidate');
        $this->assertEmpty($result);
    }

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂 generateResource 杩斿洖绌哄瓧绗︿覆锛坢iddleware锛?
     *
     * Requirements: 2.10
     */
    public function testGenerateMiddlewareReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'middleware', 'CheckAuth');
        $this->assertEmpty($result);
    }

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂 generateResource 杩斿洖绌哄瓧绗︿覆锛坋vent锛?
     *
     * Requirements: 2.10
     */
    public function testGenerateEventReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'event', 'UserCreated');
        $this->assertEmpty($result);
    }

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂 generateResource 杩斿洖绌哄瓧绗︿覆锛坙istener锛?
     *
     * Requirements: 2.10
     */
    public function testGenerateListenerReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'listener', 'SendNotification');
        $this->assertEmpty($result);
    }

    /**
     * 娴嬭瘯鐩爣妯″潡涓嶅瓨鍦ㄦ椂 generateResource 杩斿洖绌哄瓧绗︿覆锛坈ommand锛?
     *
     * Requirements: 2.10
     */
    public function testGenerateCommandReturnsEmptyWhenModuleNotExists(): void
    {
        $result = $this->generator->generateResource('NonExistentModule', 'command', 'sync-data');
        $this->assertEmpty($result);
    }

    // ==================== 鍚嶇О杞崲娴嬭瘯 ====================

    /**
     * 娴嬭瘯璧勬簮鍚嶇О缁?StudlyCase 杞崲鍚庝綔涓虹被鍚?
     *
     * Requirements: 2.11
     */
    public function testResourceNameIsConvertedToStudlyCase(): void
    {
        $this->createModuleDirectory('Blog');

        $filePath = $this->generator->generateResource('Blog', 'controller', 'user-profile');
        $content = file_get_contents($filePath);

        // user-profile 缁?studlyCase 杞负 UserProfile
        $this->assertStringContainsString('UserProfile', $content);
    }

    /**
     * 娴嬭瘯澶氭妯″潡鍚嶏紙甯﹁繛瀛楃锛夌殑鍛藉悕绌洪棿姝ｇ‘鎬?
     *
     * Requirements: 2.11
     */
    public function testResourceInMultiWordModuleHasCorrectNamespace(): void
    {
        // 娉ㄦ剰锛歡enerateResource 鍐呴儴瀵?module 鍙傛暟涔熶細璋冪敤 studlyCase
        // 鎵€浠ヤ紶鍏?'user-center' 浼氳杞崲涓?'UserCenter'
        // 浣?getModulePath 浣跨敤杞崲鍚庣殑鍚嶇О妫€鏌ョ洰褰曟槸鍚﹀瓨鍦?
        $this->createModuleDirectory('UserCenter');

        // 浣跨敤杩炲瓧绗︽牸寮忕殑妯″潡鍚嶏紝generateResource 鍐呴儴浼?studlyCase 杞崲
        $filePath = $this->generator->generateResource('user-center', 'service', 'order-service');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('app\\UserCenter\\service', $content);
        $this->assertStringContainsString('OrderService', $content);
    }

    // ==================== 杈呭姪鏂规硶 ====================

    /**
     * 鍦ㄤ复鏃剁洰褰曚腑鍒涘缓妯″潡鐩綍缁撴瀯
     */
    private function createModuleDirectory(string $moduleName): void
    {
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'controller', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'model', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'service', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'validate', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'middleware', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'event', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'listener', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'command', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders', 0755, true);
    }

    /**
     * 鍒涘缓鍩虹 Stub 鏂囦欢
     */
    private function createStubFiles(): void
    {
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

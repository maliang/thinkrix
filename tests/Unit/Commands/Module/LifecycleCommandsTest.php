<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\EnableModuleCommand;
use Thinkrix\Commands\Module\DisableModuleCommand;
use Thinkrix\Commands\Module\DeleteModuleCommand;
use Thinkrix\Commands\Module\ListModuleCommand;
use Thinkrix\Commands\Module\BaseModuleCommand;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * 妯″潡鐢熷懡鍛ㄦ湡绠＄悊鍛戒护鍗曞厓娴嬭瘯
 *
 * 娴嬭瘯鍚敤/绂佺敤/鍒犻櫎/鍒楄〃鍛戒护鐨勯厤缃纭€с€佺被缁ф壙缁撴瀯锛?
 * 浠ュ強 DeleteModuleCommand 鐨勭洰褰曞垹闄ら€昏緫銆?
 *
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6
 */
class LifecycleCommandsTest extends TestCase
{
    private string $tempDir;
    private string $packageStubDir;
    private string $customStubDir;
    private ModuleGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        // 鍒涘缓涓存椂鐩綍妯℃嫙椤圭洰缁撴瀯
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lifecycle_cmd_test_' . uniqid();
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

    // ==================== EnableModuleCommand 閰嶇疆娴嬭瘯 ====================

    /**
     * 娴嬭瘯 EnableModuleCommand 鍛戒护鍚嶇О閰嶇疆姝ｇ‘
     *
     * Requirements: 3.1
     */
    public function testEnableCommandNameIsCorrect(): void
    {
        $command = new EnableModuleCommand();
        $this->assertEquals('thinkrix:module-enable', $command->getName());
    }

    /**
     * 娴嬭瘯 EnableModuleCommand 瀹氫箟浜嗗繀椤荤殑 name 鍙傛暟
     *
     * Requirements: 3.1, 3.5
     */
    public function testEnableCommandHasRequiredNameArgument(): void
    {
        $command = new EnableModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('name'));
        $argument = $definition->getArgument('name');
        $this->assertTrue($argument->isRequired());
    }

    /**
     * 娴嬭瘯 EnableModuleCommand 鏈夋弿杩颁俊鎭?
     *
     * Requirements: 3.1
     */
    public function testEnableCommandHasDescription(): void
    {
        $command = new EnableModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 娴嬭瘯 EnableModuleCommand 缁ф壙鑷?BaseModuleCommand
     *
     * Requirements: 3.1
     */
    public function testEnableCommandExtendsBaseModuleCommand(): void
    {
        $command = new EnableModuleCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    // ==================== DisableModuleCommand 閰嶇疆娴嬭瘯 ====================

    /**
     * 娴嬭瘯 DisableModuleCommand 鍛戒护鍚嶇О閰嶇疆姝ｇ‘
     *
     * Requirements: 3.2
     */
    public function testDisableCommandNameIsCorrect(): void
    {
        $command = new DisableModuleCommand();
        $this->assertEquals('thinkrix:module-disable', $command->getName());
    }

    /**
     * 娴嬭瘯 DisableModuleCommand 瀹氫箟浜嗗繀椤荤殑 name 鍙傛暟
     *
     * Requirements: 3.2, 3.5
     */
    public function testDisableCommandHasRequiredNameArgument(): void
    {
        $command = new DisableModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('name'));
        $argument = $definition->getArgument('name');
        $this->assertTrue($argument->isRequired());
    }

    /**
     * 娴嬭瘯 DisableModuleCommand 鏈夋弿杩颁俊鎭?
     *
     * Requirements: 3.2
     */
    public function testDisableCommandHasDescription(): void
    {
        $command = new DisableModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 娴嬭瘯 DisableModuleCommand 缁ф壙鑷?BaseModuleCommand
     *
     * Requirements: 3.2
     */
    public function testDisableCommandExtendsBaseModuleCommand(): void
    {
        $command = new DisableModuleCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    // ==================== DeleteModuleCommand 閰嶇疆娴嬭瘯 ====================

    /**
     * 娴嬭瘯 DeleteModuleCommand 鍛戒护鍚嶇О閰嶇疆姝ｇ‘
     *
     * Requirements: 3.3
     */
    public function testDeleteCommandNameIsCorrect(): void
    {
        $command = new DeleteModuleCommand();
        $this->assertEquals('thinkrix:module-delete', $command->getName());
    }

    /**
     * 娴嬭瘯 DeleteModuleCommand 瀹氫箟浜嗗繀椤荤殑 name 鍙傛暟
     *
     * Requirements: 3.3, 3.5
     */
    public function testDeleteCommandHasRequiredNameArgument(): void
    {
        $command = new DeleteModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('name'));
        $argument = $definition->getArgument('name');
        $this->assertTrue($argument->isRequired());
    }

    /**
     * 娴嬭瘯 DeleteModuleCommand 鏈夋弿杩颁俊鎭?
     *
     * Requirements: 3.3, 3.6
     */
    public function testDeleteCommandHasDescription(): void
    {
        $command = new DeleteModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 娴嬭瘯 DeleteModuleCommand 缁ф壙鑷?BaseModuleCommand
     *
     * Requirements: 3.3
     */
    public function testDeleteCommandExtendsBaseModuleCommand(): void
    {
        $command = new DeleteModuleCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    // ==================== ListModuleCommand 閰嶇疆娴嬭瘯 ====================

    /**
     * 娴嬭瘯 ListModuleCommand 鍛戒护鍚嶇О閰嶇疆姝ｇ‘
     *
     * Requirements: 3.4
     */
    public function testListCommandNameIsCorrect(): void
    {
        $command = new ListModuleCommand();
        $this->assertEquals('thinkrix:module-list', $command->getName());
    }

    /**
     * 娴嬭瘯 ListModuleCommand 鏈夋弿杩颁俊鎭?
     *
     * Requirements: 3.4
     */
    public function testListCommandHasDescription(): void
    {
        $command = new ListModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 娴嬭瘯 ListModuleCommand 娌℃湁蹇呴』鐨勫弬鏁?
     *
     * Requirements: 3.4
     */
    public function testListCommandHasNoRequiredArguments(): void
    {
        $command = new ListModuleCommand();
        $definition = $command->getDefinition();
        $arguments = $definition->getArguments();

        // ListModuleCommand 涓嶅簲瀹氫箟浠讳綍鍙傛暟
        $requiredArgs = array_filter($arguments, fn($arg) => $arg->isRequired());
        $this->assertEmpty($requiredArgs, 'ListModuleCommand should not have any required arguments');
    }

    /**
     * 娴嬭瘯 ListModuleCommand 缁ф壙鑷?BaseModuleCommand
     *
     * Requirements: 3.4
     */
    public function testListCommandExtendsBaseModuleCommand(): void
    {
        $command = new ListModuleCommand();
        $this->assertInstanceOf(BaseModuleCommand::class, $command);
    }

    // ==================== DeleteModuleCommand 鐩綍鍒犻櫎閫昏緫娴嬭瘯 ====================

    /**
     * 娴嬭瘯閫氳繃 ModuleGenerator 楠岃瘉妯″潡瀛樺湪鎬э紙鍒犻櫎鍓嶆鏌ワ級
     *
     * 妯℃嫙 DeleteModuleCommand 涓?validateModuleExists 鐨勯€昏緫锛?
     * 妯″潡鐩綍瀛樺湪鏃?moduleExists 杩斿洖 true
     *
     * Requirements: 3.3, 3.5
     */
    public function testModuleExistsBeforeDeletion(): void
    {
        // 鍒涘缓妯″潡鐩綍妯℃嫙宸插瓨鍦ㄧ殑妯″潡
        $moduleName = 'TestModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        // moduleExists 搴旇繑鍥?true
        $this->assertTrue($this->generator->moduleExists($moduleName));
    }

    /**
     * 娴嬭瘯妯″潡涓嶅瓨鍦ㄦ椂 moduleExists 杩斿洖 false锛堟ā鎷熼敊璇彁绀哄満鏅級
     *
     * Requirements: 3.5
     */
    public function testModuleDoesNotExistReturnsError(): void
    {
        $moduleName = 'NonExistentModule';

        // moduleExists 搴旇繑鍥?false
        $this->assertFalse($this->generator->moduleExists($moduleName));
    }

    /**
     * 娴嬭瘯 removeDirectory 閫昏緫锛氶€掑綊鍒犻櫎鍖呭惈鏂囦欢鍜屽瓙鐩綍鐨勬ā鍧楃洰褰?
     *
     * 閫氳繃鍙嶅皠璋冪敤 DeleteModuleCommand 鐨?removeDirectory 绉佹湁鏂规硶锛?
     * 楠岃瘉鍏惰兘姝ｇ‘閫掑綊鍒犻櫎鐩綍缁撴瀯銆?
     *
     * Requirements: 3.3, 3.6
     */
    public function testRemoveDirectoryDeletesRecursively(): void
    {
        $command = new DeleteModuleCommand();

        // 鍒涘缓宓屽鐩綍缁撴瀯妯℃嫙妯″潡鐩綍
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'module_to_delete';
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'controller', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'model', 0755, true);
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations', 0755, true);

        // 鍒涘缓涓€浜涙枃浠?
        file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'module.json', '{"name": "Test"}');
        file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php', '<?php');
        file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . 'User.php', '<?php');
        file_put_contents(
            $modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '001_create_table.php',
            '<?php'
        );

        // 纭鐩綍鍜屾枃浠跺瓨鍦?
        $this->assertDirectoryExists($modulePath);
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php');

        // 閫氳繃鍙嶅皠璋冪敤 removeDirectory 绉佹湁鏂规硶
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('removeDirectory');
        $method->setAccessible(true);
        $method->invoke($command, $modulePath);

        // 楠岃瘉鐩綍宸茶瀹屽叏鍒犻櫎
        $this->assertDirectoryDoesNotExist($modulePath);
    }

    /**
     * 娴嬭瘯 removeDirectory 瀵逛笉瀛樺湪鐨勭洰褰曚笉浼氭姤閿?
     *
     * Requirements: 3.3
     */
    public function testRemoveDirectoryHandlesNonExistentPath(): void
    {
        $command = new DeleteModuleCommand();
        $nonExistentPath = $this->tempDir . DIRECTORY_SEPARATOR . 'non_existent_dir';

        // 閫氳繃鍙嶅皠璋冪敤 removeDirectory锛屼笉搴旀姏鍑哄紓甯?
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('removeDirectory');
        $method->setAccessible(true);

        // 涓嶅簲鎶涘嚭浠讳綍寮傚父
        $method->invoke($command, $nonExistentPath);
        $this->assertDirectoryDoesNotExist($nonExistentPath);
    }

    /**
     * 娴嬭瘯鍒犻櫎鍓嶅悗 moduleExists 鐘舵€佸彉鍖?
     *
     * 妯℃嫙瀹屾暣鐨勫垹闄ゆ祦绋嬶細妯″潡瀛樺湪 鈫?鍒犻櫎鐩綍 鈫?妯″潡涓嶅瓨鍦?
     *
     * Requirements: 3.3, 3.5, 3.6
     */
    public function testModuleExistsStateChangeAfterDeletion(): void
    {
        $moduleName = 'ModuleToDelete';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;

        // 鍒涘缓妯″潡鐩綍
        mkdir($modulePath . DIRECTORY_SEPARATOR . 'controller', 0755, true);
        file_put_contents($modulePath . DIRECTORY_SEPARATOR . 'module.json', '{"name": "ModuleToDelete"}');

        // 鍒犻櫎鍓嶏細妯″潡瀛樺湪
        $this->assertTrue($this->generator->moduleExists($moduleName));

        // 妯℃嫙鍒犻櫎鎿嶄綔锛堥€掑綊鍒犻櫎鐩綍锛?
        $this->removeDirectory($modulePath);

        // 鍒犻櫎鍚庯細妯″潡涓嶅瓨鍦?
        $this->assertFalse($this->generator->moduleExists($moduleName));
    }

    /**
     * 娴嬭瘯鍚嶇О杞崲鍦ㄧ敓鍛藉懆鏈熷懡浠や腑鐨勪娇鐢?
     *
     * 楠岃瘉 studlyCase 杞崲涓?moduleExists 妫€鏌ョ殑閰嶅悎浣跨敤锛?
     * 杩欐槸 Enable/Disable/Delete 鍛戒护鍏辩敤鐨勬墽琛屾祦绋嬨€?
     *
     * Requirements: 3.1, 3.2, 3.3, 3.5
     */
    public function testNameConversionForLifecycleCommands(): void
    {
        // 鍒涘缓 UserCenter 妯″潡鐩綍
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        mkdir($modulePath, 0755, true);

        // 妯℃嫙鍛戒护娴佺▼锛歬ebab-case 杈撳叆 鈫?studlyCase 杞崲 鈫?妫€鏌ュ瓨鍦ㄦ€?
        $studlyName = $this->generator->studlyCase('user-center');
        $this->assertEquals('UserCenter', $studlyName);
        $this->assertTrue($this->generator->moduleExists($studlyName));

        // snake_case 杈撳叆鍚屾牱鍙互杞崲
        $studlyName2 = $this->generator->studlyCase('user_center');
        $this->assertEquals('UserCenter', $studlyName2);
        $this->assertTrue($this->generator->moduleExists($studlyName2));
    }

    // ==================== 杈呭姪鏂规硶 ====================

    /**
     * 鍒涘缓鍩虹 Stub 鏂囦欢
     */
    private function createStubFiles(): void
    {
        file_put_contents(
            $this->packageStubDir . DIRECTORY_SEPARATOR . 'module.json.stub',
            '{"name": "{{MODULE_NAME}}", "alias": "{{LOWER_NAME}}", "enabled": true}'
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

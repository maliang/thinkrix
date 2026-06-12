<?php

declare(strict_types=1);

namespace Thinkrix\Tests\Unit\Commands\Module;

use PHPUnit\Framework\TestCase;
use Thinkrix\Commands\Module\MakeModuleCommand;
use Thinkrix\Support\ModuleGenerator;
use Thinkrix\Support\StubResolver;

/**
 * MakeModuleCommand 单元测试
 *
 * 由于 MakeModuleCommand 是 ModuleGenerator 的薄包装层，
 * 本测试重点验证命令配置正确性和通过生成器的集成行为。
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

        // 创建临时目录模拟项目结构
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'make_module_cmd_test_' . uniqid();
        $this->packageStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubDir = $this->tempDir . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';

        mkdir($this->packageStubDir, 0755, true);
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'app', 0755, true);

        // 创建基础 stub 模板
        $this->createStubFiles();

        // 使用可测试的 StubResolver 和 ModuleGenerator
        $stubResolver = $this->createStubResolver();
        $this->generator = $this->createGenerator($stubResolver);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    // ==================== 命令配置测试 ====================

    /**
     * 测试命令名称配置正确
     */
    public function testCommandNameIsCorrect(): void
    {
        $command = new MakeModuleCommand();
        $this->assertEquals('thinkrix:module-make', $command->getName());
    }

    /**
     * 测试命令描述已配置
     */
    public function testCommandHasDescription(): void
    {
        $command = new MakeModuleCommand();
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * 测试命令定义了 name 参数
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
     * 测试命令定义了 --plain 选项
     */
    public function testCommandHasPlainOption(): void
    {
        $command = new MakeModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('plain'));
    }

    /**
     * 测试命令定义了 --title 选项
     */
    public function testCommandHasTitleOption(): void
    {
        $command = new MakeModuleCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('title'));
    }

    // ==================== 标准模式目录结构生成测试 ====================

    /**
     * 测试标准模式创建完整目录结构（模拟命令执行逻辑）
     *
     * 验证通过 ModuleGenerator::createModule() 生成的标准目录结构包含：
     * controller, model, service, config, database/migrations, database/seeders, route, module.json
     *
     * 注意：createModule 内部会调用 studlyCase，所以直接传入原始名称即可
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

        // 验证标准目录结构
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'controller');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'model');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'service');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'config');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'route');

        // 验证 module.json 文件存在
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');

        // 验证标准模式下的示例文件存在
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php');
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php');
    }

    // ==================== --plain 选项最小结构测试 ====================

    /**
     * 测试 --plain 选项仅生成最小结构（目录 + module.json，不含示例文件）
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

        // 目录结构仍然存在
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'controller');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'model');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'service');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'config');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders');
        $this->assertDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'route');

        // module.json 始终存在
        $this->assertFileExists($modulePath . DIRECTORY_SEPARATOR . 'module.json');

        // 示例文件不应存在
        $this->assertFileDoesNotExist($modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');
        $this->assertFileDoesNotExist($modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php');
        $this->assertFileDoesNotExist($modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php');
    }

    /**
     * 测试 --plain 模式下 module.json 内容正确
     *
     * 使用原始输入名称传入 createModule（内部会自动转换为 StudlyCase）
     *
     * Requirements: 1.6
     */
    public function testPlainModeModuleJsonContainsModuleName(): void
    {
        // 使用带分隔符的原始名称，createModule 内部会转为 StudlyCase
        $this->generator->createModule('test-module', ['plain' => true]);

        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'TestModule';
        $content = file_get_contents($modulePath . DIRECTORY_SEPARATOR . 'module.json');

        $this->assertStringContainsString('TestModule', $content);
        $this->assertStringContainsString('testmodule', $content);
    }

    // ==================== 同名模块已存在时的错误处理测试 ====================

    /**
     * 测试同名模块已存在时 createModule 返回 false
     *
     * 在命令中此返回值会触发错误输出并返回退出码 1
     *
     * Requirements: 1.4
     */
    public function testModuleAlreadyExistsReturnsFalse(): void
    {
        $moduleName = 'ExistingModule';

        // 预创建模块目录
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        // moduleExists 应返回 true
        $this->assertTrue($this->generator->moduleExists($moduleName));

        // createModule 应返回 false
        $result = $this->generator->createModule($moduleName);
        $this->assertFalse($result);
    }

    /**
     * 测试同名模块存在时不会修改现有目录内容
     *
     * Requirements: 1.4
     */
    public function testModuleAlreadyExistsDoesNotModifyExistingDirectory(): void
    {
        $moduleName = 'ExistingModule';
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $moduleName;
        mkdir($modulePath, 0755, true);

        // 在模块目录中创建一个标记文件
        $markerFile = $modulePath . DIRECTORY_SEPARATOR . 'marker.txt';
        file_put_contents($markerFile, 'original content');

        // 尝试创建同名模块
        $this->generator->createModule($moduleName);

        // 标记文件应保持不变
        $this->assertFileExists($markerFile);
        $this->assertEquals('original content', file_get_contents($markerFile));

        // 不应生成 module.json（因为操作被终止）
        $this->assertFileDoesNotExist($modulePath . DIRECTORY_SEPARATOR . 'module.json');
    }

    /**
     * 测试命令执行流程中模块存在检测使用 StudlyCase 名称
     *
     * Requirements: 1.3, 1.4
     */
    public function testModuleExistsCheckUsesStudlyCaseName(): void
    {
        // 创建 UserCenter 目录
        $modulePath = $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter';
        mkdir($modulePath, 0755, true);

        // 模拟命令流程：先转换名称，再检查存在性
        $studlyName = $this->generator->studlyCase('user-center');
        $this->assertEquals('UserCenter', $studlyName);
        $this->assertTrue($this->generator->moduleExists($studlyName));

        // 创建应失败
        $result = $this->generator->createModule($studlyName);
        $this->assertFalse($result);
    }

    // ==================== 名称转换测试（命令特定场景） ====================

    /**
     * 测试 kebab-case 输入（user-center → UserCenter）
     *
     * Requirements: 1.3
     */
    public function testNameConversionKebabCase(): void
    {
        $inputName = 'user-center';
        $moduleName = $this->generator->studlyCase($inputName);

        $this->assertEquals('UserCenter', $moduleName);

        // 验证生成的目录使用转换后的名称
        $this->generator->createModule($moduleName);
        $this->assertDirectoryExists(
            $this->tempDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'UserCenter'
        );
    }

    /**
     * 测试 snake_case 输入（user_center → UserCenter）
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
     * 测试空格分隔输入（user center → UserCenter）
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
     * 测试单个单词输入（blog → Blog）
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
     * 测试多段连字符输入（my-awesome-module → MyAwesomeModule）
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
     * 测试包含数字的名称（module2-test → Module2Test）
     *
     * Requirements: 1.3
     */
    public function testNameConversionWithNumbers(): void
    {
        $inputName = 'module2-test';
        $moduleName = $this->generator->studlyCase($inputName);

        $this->assertEquals('Module2Test', $moduleName);
    }

    // ==================== 命令执行流程集成测试 ====================

    /**
     * 测试完整的命令执行流程（模拟）：输入名称 → StudlyCase → 检查 → 创建
     *
     * 注意：createModule 内部会再次调用 studlyCase，所以传入原始名称即可
     *
     * Requirements: 1.1, 1.3
     */
    public function testCommandExecutionFlowWithNameConversion(): void
    {
        // 模拟命令接收 'user-center' 输入
        $inputName = 'user-center';

        // 步骤 1：转换名称（命令层行为）
        $moduleName = $this->generator->studlyCase($inputName);
        $this->assertEquals('UserCenter', $moduleName);

        // 步骤 2：检查模块是否存在
        $this->assertFalse($this->generator->moduleExists($moduleName));

        // 步骤 3：使用原始名称创建模块（createModule 内部会执行 studlyCase）
        $result = $this->generator->createModule($inputName, [
            'plain' => false,
            'title' => $moduleName,
        ]);
        $this->assertTrue($result);

        // 步骤 4：验证模块现在存在（使用 StudlyCase 名称检查）
        $this->assertTrue($this->generator->moduleExists($moduleName));
    }

    /**
     * 测试 module.json 包含正确的模块名称（标准模式）
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
     * 测试标准模式下控制器命名空间正确
     *
     * Requirements: 1.2
     */
    public function testStandardModeControllerHasCorrectNamespace(): void
    {
        // 使用带分隔符的原始名称，createModule 内部会转为 StudlyCase
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

    // ==================== 辅助方法 ====================

    /**
     * 创建基础 Stub 文件
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
     * 创建可测试的 StubResolver 实例
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
     * 创建可测试的 ModuleGenerator 实例（覆盖 getModulePath 使用临时目录）
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

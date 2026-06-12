<?php

namespace Thinkrix\Support;

/**
 * 模块生成器
 *
 * 负责创建模块骨架目录结构和生成模块内资源文件。
 * 依赖 StubResolver 处理模板解析与占位符替换。
 */
class ModuleGenerator
{
    /**
     * Stub 模板解析器
     */
    protected StubResolver $stubResolver;

    /**
     * 标准模块目录结构
     */
    protected array $standardDirectories = [
        'controller',
        'model',
        'service',
        'validate',
        'middleware',
        'event',
        'listener',
        'command',
        'config',
        'database/migrations',
        'database/seeders',
        'route',
    ];

    /**
     * 资源类型与目录的映射关系
     */
    protected array $resourceTypeMap = [
        'controller'  => 'controller',
        'model'       => 'model',
        'service'     => 'service',
        'migration'   => 'database/migrations',
        'seeder'      => 'database/seeders',
        'validate'    => 'validate',
        'middleware'   => 'middleware',
        'event'       => 'event',
        'listener'    => 'listener',
        'command'     => 'command',
    ];

    /**
     * 资源类型与 Stub 文件名的映射关系
     */
    protected array $resourceStubMap = [
        'controller'  => 'controller.stub',
        'model'       => 'model.stub',
        'service'     => 'service.stub',
        'migration'   => 'migration.stub',
        'seeder'      => 'seeder.stub',
        'validate'    => 'validate.stub',
        'middleware'   => 'middleware.stub',
        'event'       => 'event.stub',
        'listener'    => 'listener.stub',
        'command'     => 'command.stub',
    ];

    public function __construct(?StubResolver $stubResolver = null)
    {
        $this->stubResolver = $stubResolver ?? new StubResolver();
    }

    /**
     * 将名称转换为 StudlyCase（大驼峰）
     *
     * 支持以下分隔符：空格、下划线、连字符、以及大小写交替
     * 示例：
     *   - user-center → UserCenter
     *   - user_center → UserCenter
     *   - user center → UserCenter
     *   - userCenter → UserCenter
     *
     * @param string $name 输入名称
     * @return string StudlyCase 格式名称
     */
    public function studlyCase(string $name): string
    {
        $name = preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', ' ', $name);

        // 将分隔符（连字符、下划线、空格）替换为空格，然后对每个单词首字母大写
        $name = str_replace(['-', '_'], ' ', $name);

        // 按空格分割，过滤空字符串
        $words = array_filter(explode(' ', $name), function ($word) {
            return $word !== '';
        });

        $result = '';
        foreach ($words as $word) {
            // 先移除非字母数字字符
            $cleaned = preg_replace('/[^A-Za-z0-9]/', '', $word);
            if ($cleaned !== '') {
                // 将第一个字母大写（即使前面有数字）
                $cleaned = strtolower($cleaned);
                $cleaned = preg_replace_callback('/[a-z]/', function ($matches) {
                    return strtoupper($matches[0]);
                }, $cleaned, 1);
                $result .= $cleaned;
            }
        }

        // 移除前导数字，确保结果以大写字母开头（合法 PSR-4 命名空间要求）
        $result = ltrim($result, '0123456789');

        return $result;
    }

    /**
     * 检查模块是否已存在
     *
     * @param string $module 模块名称（StudlyCase）
     * @return bool
     */
    public function moduleExists(string $module): bool
    {
        return is_dir($this->getModulePath($module));
    }

    /**
     * 获取模块根路径
     *
     * @param string $module 模块名称（StudlyCase）
     * @return string 完整的模块路径
     */
    public function getModulePath(string $module): string
    {
        $paths = config('thinkrix.modules.paths', ['Modules']);
        return app()->getRootPath() . $paths[0] . DIRECTORY_SEPARATOR . $module;
    }

    /**
     * 创建完整模块骨架
     *
     * @param string $name 模块名称（将自动转换为 StudlyCase）
     * @param array $options 选项：
     *   - plain: bool — 仅创建目录结构，不生成示例文件
     *   - title: string — 模块标题
     * @return bool 创建成功返回 true
     */
    public function createModule(string $name, array $options = []): bool
    {
        $moduleName = $this->studlyCase($name);
        $modulePath = $this->getModulePath($moduleName);

        // 同名模块已存在，终止操作
        if (is_dir($modulePath)) {
            return false;
        }

        $isPlain = !empty($options['plain']);
        $lowerName = strtolower($moduleName);

        // 创建标准目录结构
        foreach ($this->standardDirectories as $dir) {
            $dirPath = $modulePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
            }
        }

        // 基础占位符映射
        $replacements = [
            '{{MODULE_NAME}}' => $moduleName,
            '{{TITLE}}'       => $options['title'] ?? $moduleName,
            '{{LOWER_NAME}}'  => $lowerName,
            '{{NAMESPACE}}'   => "app\\{$moduleName}",
            '{{CLASS_NAME}}'  => $moduleName,
            '{{TABLE_NAME}}'  => '',
            '{{TIMESTAMP}}'   => date('YmdHis'),
        ];

        // 始终生成 module.json
        $moduleJsonContent = $this->stubResolver->resolve('module.json.stub', $replacements);
        if (!empty($moduleJsonContent)) {
            file_put_contents(
                $modulePath . DIRECTORY_SEPARATOR . 'module.json',
                $moduleJsonContent
            );
        }

        // 始终生成 composer.json（模块可声明自身依赖）
        $composerJsonContent = $this->stubResolver->resolve('composer.json.stub', $replacements);
        if (!empty($composerJsonContent)) {
            file_put_contents(
                $modulePath . DIRECTORY_SEPARATOR . 'composer.json',
                $composerJsonContent
            );
        }

        // 非 plain 模式下生成示例文件
        if (!$isPlain) {
            // 生成配置文件
            $configContent = $this->stubResolver->resolve('config.stub', $replacements);
            if (!empty($configContent)) {
                file_put_contents(
                    $modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php',
                    $configContent
                );
            }

            // 生成路由文件
            $routeContent = $this->stubResolver->resolve('route.stub', $replacements);
            if (!empty($routeContent)) {
                file_put_contents(
                    $modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php',
                    $routeContent
                );
            }

            // 生成示例控制器
            $controllerReplacements = array_merge($replacements, [
                '{{NAMESPACE}}' => "app\\{$moduleName}\\controller",
                '{{CLASS_NAME}}' => 'Index',
            ]);
            $controllerContent = $this->stubResolver->resolve('controller.stub', $controllerReplacements);
            if (!empty($controllerContent)) {
                file_put_contents(
                    $modulePath . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'Index.php',
                    $controllerContent
                );
            }
        }

        return true;
    }

    /**
     * 在指定模块内生成资源文件
     *
     * @param string $module 模块名称（StudlyCase）
     * @param string $type 资源类型（controller, model, service, migration, seeder, validate, middleware, event, listener, command）
     * @param string $name 资源名称
     * @param array $options 额外选项：
     *   - plain: bool — 使用简洁模板（仅 controller 类型支持）
     * @return string 生成的文件完整路径，失败返回空字符串
     */
    public function generateResource(string $module, string $type, string $name, array $options = []): string
    {
        $moduleName = $this->studlyCase($module);
        $modulePath = $this->getModulePath($moduleName);

        // 检查模块是否存在
        if (!is_dir($modulePath)) {
            return '';
        }

        // 验证资源类型
        if (!isset($this->resourceTypeMap[$type])) {
            return '';
        }

        $directory = $this->resourceTypeMap[$type];
        $lowerName = strtolower($moduleName);
        $className = $this->studlyCase($name);

        // 确定命名空间（migration 类型不需要命名空间目录）
        $namespace = "app\\{$moduleName}\\{$directory}";
        if ($type === 'migration' || $type === 'seeder') {
            // migration 和 seeder 使用 database 子目录，命名空间保持到 database 层
            $namespace = "app\\{$moduleName}\\database";
        }

        // 构建表名（snake_case）
        $tableName = $this->toSnakeCase($name);

        // 构建占位符映射
        $replacements = [
            '{{MODULE_NAME}}' => $moduleName,
            '{{LOWER_NAME}}'  => $lowerName,
            '{{NAMESPACE}}'   => $namespace,
            '{{CLASS_NAME}}'  => $className,
            '{{TABLE_NAME}}'  => $tableName,
            '{{TIMESTAMP}}'   => date('YmdHis'),
        ];

        // 确定 Stub 文件名
        $stubName = $this->resourceStubMap[$type];
        if ($type === 'controller' && !empty($options['plain'])) {
            $stubName = 'controller.plain.stub';
        }

        // 解析模板
        $content = $this->stubResolver->resolve($stubName, $replacements);
        if (empty($content)) {
            return '';
        }

        // 确定目标文件路径
        $targetDir = $modulePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 生成文件名
        $filename = $this->buildFilename($type, $className, $tableName);
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        // 写入文件
        file_put_contents($filePath, $content);

        return $filePath;
    }

    /**
     * 将名称转换为 snake_case
     *
     * @param string $name 输入名称
     * @return string snake_case 格式名称
     */
    protected function toSnakeCase(string $name): string
    {
        // 先转为 StudlyCase，然后在大写字母前插入下划线
        $studly = $this->studlyCase($name);

        // 在大写字母前插入下划线（首字母除外）
        $snake = preg_replace('/([a-z\d])([A-Z])/', '$1_$2', $studly);

        return strtolower($snake);
    }

    /**
     * 构建资源文件名
     *
     * @param string $type 资源类型
     * @param string $className 类名
     * @param string $tableName 表名（用于 migration）
     * @return string 文件名
     */
    protected function buildFilename(string $type, string $className, string $tableName): string
    {
        if ($type === 'migration') {
            // 迁移文件名包含时间戳前缀
            $timestamp = date('YmdHis');
            return $timestamp . '_create_' . $tableName . '_table.php';
        }

        return $className . '.php';
    }
}

<?php

namespace Thinkrix\Support;

use think\App;
use think\facade\Event;
use Thinkrix\Models\Module;

/**
 * 模块加载器
 *
 * 负责在应用启动时加载所有已启用模块的资源，包括：
 * - 配置文件（带优先级机制）
 * - 路由文件（条件加载）
 * - 中间件注册（基于 module.json 声明）
 * - 事件监听器注册（基于 module.json 声明）
 * - 自定义命令注册（扫描 command/ 目录）
 */
class ModuleLoader
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 已注册的命令类列表
     */
    protected array $registeredCommands = [];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 加载所有已启用模块的资源
     *
     * 查询数据库获取已启用模块列表，依次加载各模块的配置、路由、
     * 中间件、事件监听器和自定义命令。
     */
    public function loadEnabledModules(): void
    {
        try {
            $modules = Module::where('enabled', true)->select();
        } catch (\Throwable $e) {
            // 数据库连接失败或表不存在时，静默跳过（安装阶段可能尚未建表）
            return;
        }

        foreach ($modules as $module) {
            $moduleName = $module->name;

            // 依次在所有模块目录中查找
            $modulePath = null;
            $paths = config('thinkrix.modules.paths', ['Modules']);
            $root = $this->app->getRootPath();
            foreach ($paths as $p) {
                $candidate = $root . $p . DIRECTORY_SEPARATOR . $moduleName;
                if (is_dir($candidate)) {
                    $modulePath = $candidate;
                    break;
                }
            }
            if ($modulePath === null) {
                continue;
            }

            // 读取 module.json
            $moduleJson = $this->readModuleJson($modulePath, $moduleName);

            // 加载配置
            $this->loadConfig($moduleName, $modulePath);

            // 加载路由
            $this->loadRoutes($moduleName, $modulePath);

            // 注册中间件
            $this->registerMiddleware($moduleName, $modulePath, $moduleJson);

            // 注册事件监听器
            $this->registerListeners($moduleName, $modulePath, $moduleJson);

        }
    }

    /**
     * 扫描已启用模块中的自定义命令，供服务启动阶段注册到 Console。
     */
    public function loadEnabledModuleCommands(): void
    {
        try {
            $modules = Module::where('enabled', true)->select();
        } catch (\Throwable $e) {
            return;
        }

        foreach ($modules as $module) {
            $modulePath = null;
            $paths = config('thinkrix.modules.paths', ['Modules']);
            $root = $this->app->getRootPath();
            foreach ($paths as $p) {
                $candidate = $root . $p . DIRECTORY_SEPARATOR . $module->name;
                if (is_dir($candidate)) {
                    $modulePath = $candidate;
                    break;
                }
            }
            if ($modulePath === null) {
                continue;
            }
            $this->registerCommands($module->name, $modulePath);
        }
    }

    /**
     * 加载模块配置
     *
     * 配置优先级：
     * 1. 项目 config/modules/{lower_name}.php（最高，已发布配置）
     * 2. 模块 app/{ModuleName}/config/config.php（默认配置）
     *
     * 注册键名格式：module_{lower_name}
     *
     * @param string $moduleName 模块名称（StudlyCase）
     * @param string $modulePath 模块完整路径
     */
    public function loadConfig(string $moduleName, string $modulePath): void
    {
        $lowerName = strtolower($moduleName);

        // 项目已发布配置路径（最高优先级）
        $projectConfigPath = $this->app->getRootPath() . 'config' . DIRECTORY_SEPARATOR
            . 'modules' . DIRECTORY_SEPARATOR . $lowerName . '.php';

        // 模块默认配置路径
        $moduleConfigPath = $modulePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

        $config = [];

        if (file_exists($projectConfigPath)) {
            $config = include $projectConfigPath;
        } elseif (file_exists($moduleConfigPath)) {
            $config = include $moduleConfigPath;
        }

        // 确保 config 是数组
        if (!is_array($config)) {
            $config = [];
        }

        // 注册到 ThinkPHP 配置系统
        if (!empty($config)) {
            $this->app->config->set($config, "module_{$lowerName}");
        }
    }

    /**
     * 加载模块路由
     *
     * 仅在模块 route/app.php 文件存在时加载。
     * 模块禁用时不调用此方法，因此路由不会被加载。
     *
     * @param string $moduleName 模块名称（StudlyCase）
     * @param string $modulePath 模块完整路径
     */
    public function loadRoutes(string $moduleName, string $modulePath): void
    {
        $routeFile = $modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php';

        if (file_exists($routeFile)) {
            include $routeFile;
        }
    }

    /**
     * 注册模块中间件
     *
     * 基于 module.json 中的 middleware 声明，注册全局中间件和路由中间件。
     * module.json 中 middleware 字段格式：
     * {
     *     "global": ["app\\ModuleName\\middleware\\SomeMiddleware"],
     *     "route": ["app\\ModuleName\\middleware\\AnotherMiddleware"]
     * }
     *
     * @param string $moduleName 模块名称（StudlyCase）
     * @param string $modulePath 模块完整路径
     * @param array $moduleJson module.json 解析后的数组
     */
    public function registerMiddleware(string $moduleName, string $modulePath, array $moduleJson): void
    {
        $middleware = $moduleJson['middleware'] ?? [];

        if (!is_array($middleware)) {
            return;
        }

        // 注册全局中间件
        if (!empty($middleware['global']) && is_array($middleware['global'])) {
            foreach ($middleware['global'] as $mw) {
                $this->app->middleware->add($mw);
            }
        }

        // 注册路由中间件
        if (!empty($middleware['route']) && is_array($middleware['route'])) {
            foreach ($middleware['route'] as $mw) {
                $this->app->middleware->route($mw);
            }
        }
    }

    /**
     * 注册模块事件监听器
     *
     * 基于 module.json 中的 listeners 声明，注册事件监听器。
     * module.json 中 listeners 字段格式：
     * {
     *     "event.name": "app\\ModuleName\\listener\\SomeListener",
     *     "another.event": "app\\ModuleName\\listener\\AnotherListener"
     * }
     *
     * @param string $moduleName 模块名称（StudlyCase）
     * @param string $modulePath 模块完整路径
     * @param array $moduleJson module.json 解析后的数组
     */
    public function registerListeners(string $moduleName, string $modulePath, array $moduleJson): void
    {
        $listeners = $moduleJson['listeners'] ?? [];

        if (!is_array($listeners)) {
            return;
        }

        foreach ($listeners as $event => $listener) {
            if (is_string($event) && !empty($listener)) {
                Event::listen($event, $listener);
            }
        }
    }

    /**
     * 注册模块自定义命令
     *
     * 扫描模块 command/ 目录下的所有 PHP 文件，尝试加载并注册命令类。
     * 命令类必须继承 think\console\Command 基类。
     * 遇到语法错误或不符合条件的类时，跳过并记录 warning 日志。
     *
     * @param string $moduleName 模块名称（StudlyCase）
     * @param string $modulePath 模块完整路径
     */
    public function registerCommands(string $moduleName, string $modulePath): void
    {
        $commandDir = $modulePath . DIRECTORY_SEPARATOR . 'command';

        if (!is_dir($commandDir)) {
            return;
        }

        $files = glob($commandDir . DIRECTORY_SEPARATOR . '*.php');

        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            try {
                $className = 'app\\' . $moduleName . '\\command\\' . basename($file, '.php');

                // class_exists 会触发自动加载，可能抛出语法错误
                if (class_exists($className) && is_subclass_of($className, \think\console\Command::class)) {
                    if (!in_array($className, $this->registeredCommands, true)) {
                        $this->registeredCommands[] = $className;
                    }
                }
            } catch (\Throwable $e) {
                // 捕获语法错误或其他异常，记录 warning 日志并跳过
                $this->app->log->warning(
                    "Module [{$moduleName}] command loading failed: " . $e->getMessage(),
                    ['file' => $file, 'exception' => get_class($e)]
                );
            }
        }
    }

    /**
     * 获取已注册的命令类列表
     *
     * @return array 命令类完整类名数组
     */
    public function getRegisteredCommands(): array
    {
        return $this->registeredCommands;
    }

    /**
     * 读取模块的 module.json 文件
     *
     * @param string $modulePath 模块完整路径
     * @param string $moduleName 模块名称（用于日志记录）
     * @return array 解析后的 JSON 数据，解析失败返回空数组
     */
    protected function readModuleJson(string $modulePath, string $moduleName): array
    {
        $moduleJsonPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';

        if (!file_exists($moduleJsonPath)) {
            return [];
        }

        $content = file_get_contents($moduleJsonPath);

        if ($content === false) {
            $this->app->log->warning(
                "Module [{$moduleName}] module.json read failed.",
                ['path' => $moduleJsonPath]
            );
            return [];
        }

        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->app->log->warning(
                "Module [{$moduleName}] module.json parse failed: " . json_last_error_msg(),
                ['path' => $moduleJsonPath]
            );
            return [];
        }

        return $json;
    }
}

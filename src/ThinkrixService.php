<?php

namespace Thinkrix;

use think\Route;
use think\Service;
use Thinkrix\Services\AuthService;
use Thinkrix\Services\DataDictService;
use Thinkrix\Services\ModuleService;
use Thinkrix\Services\PermissionService;
use Thinkrix\Services\RealtimeService;

class ThinkrixService extends Service
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 合并配置
        $this->mergeConfigFrom(__DIR__ . '/../config/thinkrix.php', 'thinkrix');

        // 注册单例服务
        $this->app->bind(AuthService::class, AuthService::class);
        $this->app->bind(DataDictService::class, DataDictService::class);
        $this->app->bind(ModuleService::class, ModuleService::class);
        $this->app->bind(PermissionService::class, PermissionService::class);
        $this->app->bind(RealtimeService::class, RealtimeService::class);

        // 注册 ModuleLoader 单例
        $this->app->bind(\Thinkrix\Support\ModuleLoader::class, function (\think\App $app) {
            return new \Thinkrix\Support\ModuleLoader($app);
        });
    }

    /**
     * 注册命令
     */
    protected $commands = [
        'thinkrix:install' => \Thinkrix\Commands\InstallCommand::class,
        'thinkrix:publish' => \Thinkrix\Commands\PublishAssetsCommand::class,
        'thinkrix:uninstall' => \Thinkrix\Commands\UninstallCommand::class,
        'thinkrix:make-backend' => \Thinkrix\Commands\MakeBackendCommand::class,
        'thinkrix:remove-backend' => \Thinkrix\Commands\RemoveBackendCommand::class,
        // 模块管理命令
        'thinkrix:module-list' => \Thinkrix\Commands\Module\ListModuleCommand::class,
        'thinkrix:module-make' => \Thinkrix\Commands\Module\MakeModuleCommand::class,
        'thinkrix:module-make-controller' => \Thinkrix\Commands\Module\MakeControllerCommand::class,
        'thinkrix:module-make-model' => \Thinkrix\Commands\Module\MakeModelCommand::class,
        'thinkrix:module-make-service' => \Thinkrix\Commands\Module\MakeServiceCommand::class,
        'thinkrix:module-make-migration' => \Thinkrix\Commands\Module\MakeMigrationCommand::class,
        'thinkrix:module-make-seeder' => \Thinkrix\Commands\Module\MakeSeederCommand::class,
        'thinkrix:module-make-validate' => \Thinkrix\Commands\Module\MakeValidateCommand::class,
        'thinkrix:module-make-middleware' => \Thinkrix\Commands\Module\MakeMiddlewareCommand::class,
        'thinkrix:module-make-event' => \Thinkrix\Commands\Module\MakeEventCommand::class,
        'thinkrix:module-make-listener' => \Thinkrix\Commands\Module\MakeListenerCommand::class,
        'thinkrix:module-make-command' => \Thinkrix\Commands\Module\MakeCommandCommand::class,
        'thinkrix:module-enable' => \Thinkrix\Commands\Module\EnableModuleCommand::class,
        'thinkrix:module-disable' => \Thinkrix\Commands\Module\DisableModuleCommand::class,
        'thinkrix:module-delete' => \Thinkrix\Commands\Module\DeleteModuleCommand::class,
        'thinkrix:module-migrate' => \Thinkrix\Commands\Module\MigrateCommand::class,
        'thinkrix:module-seed' => \Thinkrix\Commands\Module\SeedCommand::class,
        'thinkrix:module-publish-stubs' => \Thinkrix\Commands\Module\PublishStubsCommand::class,
        'thinkrix:module-publish-config' => \Thinkrix\Commands\Module\PublishConfigCommand::class,
        'thinkrix:module-route-list' => \Thinkrix\Commands\Module\RouteListCommand::class,
    ];

    /**
     * 启动服务
     */
    public function boot(): void
    {
        $loader = $this->app->make(\Thinkrix\Support\ModuleLoader::class);
        $loader->loadEnabledModuleCommands();

        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        // 使用 ModuleLoader 进行条件加载（替代原有的 glob 无条件加载）
        $this->registerRoutes(function (): void {
            $this->app->make(\Thinkrix\Support\ModuleLoader::class)->loadEnabledModules();
        });

        // 注册命令
        $this->commands(array_merge($this->commands, $loader->getRegisteredCommands()));
    }

    /**
     * 合并配置
     */
    protected function mergeConfigFrom(string $path, string $key): void
    {
        $config = $this->app->config;
        $config->load($path, $key);
    }

}

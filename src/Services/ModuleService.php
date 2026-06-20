<?php

namespace Thinkrix\Services;

use think\facade\Event;
use Thinkrix\Models\Module;

/**
 * ModuleService - 模块服务
 *
 * 在 ThinkPHP 中简化模块管理（替代 nwidart/laravel-modules）
 */
class ModuleService extends BaseService
{
    /**
     * 获取所有模块列表
     */
    public function getModules(): array
    {
        // 同步 filesystem 模块到数据库
        $this->syncModules();

        return Module::order('name')->select()->toArray();
    }

    /**
     * 启用模块
     */
    public function enable(string $name): bool
    {
        $module = Module::where('name', $name)->find();
        if (!$module) { return false; }

        $module->enable();

        // 触发事件
        Event::trigger('thinkrix.module.enabled', $module);

        return true;
    }

    /**
     * 禁用模块
     */
    public function disable(string $name): bool
    {
        $module = Module::where('name', $name)->find();
        if (!$module) { return false; }

        $module->disable();

        Event::trigger('thinkrix.module.disabled', $module);

        return true;
    }

    /**
     * 同步 filesystem 模块到数据库
     *
     * 扫描 app/ 目录下的模块目录结构
     */
    protected function getModulePaths(): array
    {
        $paths = config('thinkrix.modules.paths', ['Modules']);
        $root = app()->getRootPath();
        return array_map(fn($p) => $root . $p . DIRECTORY_SEPARATOR, $paths);
    }

    public function syncModules(): void
    {
        $scanPaths = $this->getModulePaths();
        $existingNames = [];

        foreach ($scanPaths as $scanDir) {
            if (!is_dir($scanDir)) { continue; }

            $dirs = glob($scanDir . '*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $name = basename($dir);
                $moduleJsonPath = $dir . DIRECTORY_SEPARATOR . 'module.json';

                // 普通 app/controller 等目录不是模块，只有声明了 module.json 才参与模块管理。
                if (!file_exists($moduleJsonPath)) {
                    continue;
                }

                $title = $name;
                $description = '';
                $version = '1.0.0';
                $author = '';
                $website = '';
                $logo = '';
                $config = [];

                $json = json_decode(file_get_contents($moduleJsonPath), true);
                $json = is_array($json) ? $json : [];
                $title = $json['title'] ?? $json['name'] ?? $name;
                $description = $json['description'] ?? '';
                $version = $json['version'] ?? '1.0.0';
                $author = $json['author'] ?? '';
                $website = $json['website'] ?? $json['url'] ?? '';
                $logo = $json['logo'] ?? '';
                $config = $json;

                $module = Module::where('name', $name)->find() ?? new Module(['name' => $name, 'enabled' => false]);
                $module->save([
                    'title' => $title,
                    'description' => $description,
                    'version' => $version,
                    'author' => $author,
                    'website' => $website,
                    'logo' => $logo,
                    'config' => $config,
                ]);

                $existingNames[] = $name;
            }
        }

        // 删除不存在的模块记录
        if (!empty($existingNames)) {
            Module::whereNotIn('name', $existingNames)->delete();
        } else {
            Module::whereRaw('1 = 1')->delete();
        }
    }

    /**
     * 删除模块的数据库注册记录
     */
    public function delete(string $name): bool
    {
        $module = Module::where('name', $name)->find();
        if (!$module) {
            return false;
        }

        $module->delete();

        // 触发事件
        Event::trigger('thinkrix.module.deleted', $module);

        return true;
    }

    /**
     * 安装模块：迁移 + 填充 + 注册菜单权限
     * 检查模块是否存在
     */
    public function exists(string $name): bool
    {
        return Module::where('name', $name)->find() !== null;
    }

    /**
     * 检查模块是否启用
     */
    public function isEnabled(string $name): bool
    {
        $module = Module::where('name', $name)->find();
        return $module && $module->isEnabled();
    }

    /**
     * 安装模块：迁移 + 填充 + 注册菜单权限
     */
    public function install(string $name): bool
    {
        $module = Module::where('name', $name)->find();
        if (!$module) { return false; }
        if ($module->enabled) { return true; }

        $modulePath = $this->findModulePath($name);
        if (!$modulePath) { return false; }

        $moduleJsonPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
        if (file_exists($moduleJsonPath)) {
            $json = json_decode(file_get_contents($moduleJsonPath), true) ?: [];
            $this->registerMenus($json, $name);
            $this->registerPermissions($json, $name);
        }

        $this->runModuleMigrate($name);
        $this->runModuleSeed($name);

        $module->enable();
        $module->save();
        Event::trigger('thinkrix.module.installed', $module);
        return true;
    }

    /**
     * 卸载模块：删除菜单权限 + 回滚迁移
     */
    public function uninstall(string $name): bool
    {
        $module = Module::where('name', $name)->find();
        if (!$module) { return false; }
        if (!$module->enabled) { return true; }

        $menuModel = config('thinkrix.models.menu', \Thinkrix\Models\Menu::class);
        $menuModel::where('module', $name)->delete();

        $permissionModel = config('thinkrix.models.permission', \Thinkrix\Models\Permission::class);
        $permissionModel::where('module', $name)->delete();

        $this->runModuleMigrate($name, true);

        $module->disable();
        $module->save();
        Event::trigger('thinkrix.module.uninstalled', $module);
        return true;
    }

    protected function findModulePath(string $name): ?string
    {
        $paths = config('thinkrix.modules.paths', ['Modules', 'app']);
        $root = app()->getRootPath();
        foreach ($paths as $p) {
            $candidate = $root . $p . DIRECTORY_SEPARATOR . $name;
            if (is_dir($candidate)) { return $candidate; }
        }
        return null;
    }

    protected function registerMenus(array $moduleJson, string $moduleName): void
    {
        $menus = $moduleJson['menus'] ?? [];
        if (empty($menus)) { return; }
        $menuModel = config('thinkrix.models.menu', \Thinkrix\Models\Menu::class);
        $guard = config('thinkrix.guard', 'admin');
        foreach ($menus as $menu) {
            $menu['guard_name'] = $guard;
            $menu['module'] = $moduleName;
            $exists = $menuModel::where('name', $menu['name'])->where('guard_name', $guard)->find();
            if (!$exists) { $menuModel::create($menu); }
        }
    }

    protected function registerPermissions(array $moduleJson, string $moduleName): void
    {
        $permissions = $moduleJson['permissions'] ?? [];
        if (empty($permissions)) { return; }
        $permissionModel = config('thinkrix.models.permission', \Thinkrix\Models\Permission::class);
        $guard = config('thinkrix.guard', 'admin');
        foreach ($permissions as $perm) {
            $perm['guard_name'] = $guard;
            $perm['module'] = $moduleName;
            $exists = $permissionModel::where('name', $perm['name'])->where('guard_name', $guard)->find();
            if (!$exists) { $permissionModel::create($perm); }
        }
    }

    protected function runModuleMigrate(string $name, bool $rollback = false): void
    {
        try {
            $console = app('console');
            $command = $console->find('thinkrix:module-migrate');
            $args = [$name];
            if ($rollback) { $args[] = '--rollback'; }
            $input = new \think\console\Input(array_merge(['thinkrix:module-migrate'], $args));
            $output = new \think\console\Output();
            $command->run($input, $output);
        } catch (\Throwable $e) {
            // 迁移失败不影响主流程
        }
    }

    protected function runModuleSeed(string $name): void
    {
        try {
            $console = app('console');
            $command = $console->find('thinkrix:module-seed');
            $input = new \think\console\Input(['thinkrix:module-seed', $name]);
            $output = new \think\console\Output();
            $command->run($input, $output);
        } catch (\Throwable $e) {
            // 填充失败不影响主流程
        }
    }
}

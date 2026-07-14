<?php

namespace Thinkrix\Services;

use think\facade\Event;
use Thinkrix\Models\Module;
use Thinkrix\Modules\Manifest\ModuleManifest;
use Thinkrix\Modules\Manifest\ModuleManifestLoader;

/**
 * ModuleService - 模块服务
 *
 * 在 ThinkPHP 中简化模块管理（替代 nwidart/laravel-modules）
 */
class ModuleService
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

        /** 同步本地模块信息与持久化状态。 */
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

                // 优先读取 Trix module.json；旧 Thinkrix module.json 会在 loader 中归一化。
                $json = $this->readModuleConfig($dir) ?? [];
                $title = $json['title'] ?? $json['name'] ?? $name;
                $description = $json['description'] ?? '';
                $version = $json['version'] ?? '1.0.0';
                $author = $json['author'] ?? '';
                $website = $json['website'] ?? $json['url'] ?? '';
                $logo = $json['logo'] ?? '';
                $config = $json;

                $module = Module::where('name', $name)->find() ?? new Module(['name' => $name, 'enabled' => true]);
                $module->save([
                    'registry_id' => $json['id'] ?? null,
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
        // 尝试从文件系统发现模块（不依赖 syncModules 前置调用）
        $module = Module::where('name', $name)->find();
        if (!$module) {
            $modulePath = $this->findModulePath($name);
            if (!$modulePath) { return false; }
            $json = $this->readModuleConfig($modulePath);
            if ($json === null) { return false; }
            $module = new Module([
                'name' => $name,
                'registry_id' => $json['id'] ?? null,
                'enabled' => false,
                'title' => $json['title'] ?? $name,
                'description' => $json['description'] ?? '',
                'version' => $json['version'] ?? '1.0.0',
                'author' => $json['author'] ?? '',
                'website' => $json['website'] ?? $json['url'] ?? '',
                'logo' => $json['logo'] ?? '',
                'config' => $json,
            ]);
            $module->save();
        }
        if ($module->enabled) { return true; }

        $modulePath = $modulePath ?? $this->findModulePath($name);
        if (!$modulePath) { return false; }

        // 注册菜单和权限
        $json = $this->readModuleConfig($modulePath) ?? [];
        $this->registerMenus($json, $name);
        $this->registerPermissions($json, $name);

        // 先启用模块（迁移/填充命令依赖 enabled 状态）
        $module->enable();
        $module->save();

        // 独立进程执行迁移和填充（避免类名冲突）
        $migrated = $this->runModuleMigrate($name);
        if (!$migrated || !$this->runModuleSeed($name)) {
            if ($migrated) {
                try {
                    $rollbackOk = $this->runModuleMigrate($name, true);
                } catch (\Throwable $rollbackError) {
                    $rollbackOk = false;
                    error_log($rollbackError->getMessage());
                }
                if (!$rollbackOk) {
                    $config = is_array($module->config) ? $module->config : [];
                    $config['lifecycle_error'] = 'install_rollback_failed';
                    $module->config = $config;
                    $module->save();
                    error_log("Thinkrix module [{$name}] install rollback failed; manual database recovery is required.");
                }
            }
            $this->removeModuleContributions($name);
            $module->disable();
            $module->save();
            return false;
        }

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

        if (!$this->runModuleMigrate($name, true)) {
            return false;
        }

        $this->removeModuleContributions($name);
        $module->disable();
        $module->save();
        Event::trigger('thinkrix.module.uninstalled', $module);
        return true;
    }

    /** 删除模块注册的菜单、权限及角色权限关联。 */
    protected function removeModuleContributions(string $name): void
    {
        $menuModel = config('thinkrix.models.menu', \Thinkrix\Models\Menu::class);
        $permissionModel = config('thinkrix.models.permission', \Thinkrix\Models\Permission::class);
        $permissionIds = $permissionModel::where('module', $name)->column('id');

        if ($permissionIds !== []) {
            \think\facade\Db::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            \think\facade\Db::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        $menuModel::where('module', $name)->delete();
        $permissionModel::where('module', $name)->delete();
    }

        /** 查找并返回匹配的业务对象。 */
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

        /** 注册当前模块贡献的数据。 */
    protected function registerMenus(array $moduleJson, string $moduleName): void
    {
        $menus = $moduleJson['menus'] ?? [];
        if (empty($menus)) { return; }
        $menuModel = config('thinkrix.models.menu', \Thinkrix\Models\Menu::class);
        $guard = config('thinkrix.guard', 'admin');
        foreach ($menus as $menu) {
            if (!isset($menu['name']) && isset($menu['key'])) {
                $menu['name'] = $menu['key'];
            }
            if (isset($menu['permission']) && !isset($menu['permissions'])) {
                $menu['permissions'] = [$menu['permission']];
            }
            unset($menu['key'], $menu['parent'], $menu['permission']);

            $menu['guard_name'] = $guard;
            $menu['module'] = $moduleName;
            $exists = $menuModel::where('name', $menu['name'])->where('guard_name', $guard)->find();
            if (!$exists) { $menuModel::create($menu); }
        }
    }

        /** 注册当前模块贡献的数据。 */
    protected function registerPermissions(array $moduleJson, string $moduleName): void
    {
        $permissions = $moduleJson['permissions'] ?? [];
        if (empty($permissions)) { return; }
        $permissionModel = config('thinkrix.models.permission', \Thinkrix\Models\Permission::class);
        $guard = config('thinkrix.guard', 'admin');
        foreach ($permissions as $perm) {
            unset($perm['group']);

            $perm['guard_name'] = $guard;
            $perm['module'] = $moduleName;
            $exists = $permissionModel::where('name', $perm['name'])->where('guard_name', $guard)->find();
            if (!$exists) { $permissionModel::create($perm); }
        }
    }

    /** 执行指定模块的数据库迁移。 */
    protected function runModuleMigrate(string $name, bool $rollback = false): bool
    {
        $arg = $rollback ? ' --rollback' : '';
        passthru('php think thinkrix:module-migrate ' . escapeshellarg($name) . $arg, $exitCode);
        return $exitCode === 0;
    }

    /** 执行指定模块的数据填充。 */
    protected function runModuleSeed(string $name): bool
    {
        passthru('php think thinkrix:module-seed ' . escapeshellarg($name), $exitCode);
        return $exitCode === 0;
    }

        /** 从指定来源读取数据。 */
    protected function readModuleJson(string $modulePath): ?array
    {
        return $this->readModuleConfig($modulePath);
    }

        /** 从指定来源读取数据。 */
    protected function readModuleConfig(string $modulePath): ?array
    {
        $manifest = (new ModuleManifestLoader())->loadFromPath($modulePath);

        if (!$manifest) {
            return null;
        }

        // 将 Trix manifest 展平为 Thinkrix 原有模块配置形态，避免后台列表和安装流程分叉。
        return $this->manifestToModuleConfig($manifest);
    }

    /**
     * 执行 manifestToModuleConfig 方法对应的具体职责。
     * @return array<string, mixed>
     */
    protected function manifestToModuleConfig(ModuleManifest $manifest): array
    {
        $manifestData = $manifest->toArray();

        return array_merge($manifestData, [
            'title' => $manifest->name() ?: ($manifestData['title'] ?? ''),
            'description' => $manifestData['description'] ?? '',
            'version' => $manifest->version() ?: ($manifestData['version'] ?? '1.0.0'),
            'menus' => $manifest->menus(),
            'permissions' => $manifest->permissions(),
        ]);
    }
}

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

                $module = Module::where('name', $name)->find() ?? new Module(['name' => $name, 'enabled' => true]);
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
}

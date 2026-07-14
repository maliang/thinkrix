<?php

namespace Thinkrix\Controllers;

use Thinkrix\Modules\Manifest\ModuleManifestLoader;
use Thinkrix\Schema\Pages\ModuleManagementSchema;
use Thinkrix\Services\ModuleManagementService;
use Thinkrix\Services\ModuleService;

/** 仅负责本地模块列表、生命周期操作和模块 Logo。 */
class ModuleController extends Controller
{
    public function __construct(
        private readonly ModuleService $modules,
        private readonly ModuleManagementService $management,
        private readonly ModuleManagementSchema $schema,
    ) {
    }

    /** 返回已安装模块页面或分页列表。 */
    public function index(): array
    {
        if ($this->input('action_type') === 'installed_ui') { return $this->schema->installed(); }
        $page = max(1, (int) $this->input('page', 1));
        $pageSize = max(1, (int) $this->input('page_size', 15));
        $modules = $this->management->modules();
        return success(['list' => array_slice($modules, ($page - 1) * $pageSize, $pageSize),
            'total' => count($modules), 'page' => $page, 'page_size' => $pageSize]);
    }

    public function enable(string $name): array { return $this->lifecycle($name, 'enable', 'module.enable_ok', 'module.enable_failed'); }
    public function disable(string $name): array { return $this->lifecycle($name, 'disable', 'module.disable_ok', 'module.disable_failed'); }
    public function install(string $name): array { return $this->lifecycle($name, 'install', 'module.install_ok', 'module.install_failed'); }
    public function uninstall(string $name): array { return $this->lifecycle($name, 'uninstall', 'module.uninstall_ok', 'module.uninstall_failed'); }

    /** 返回模块声明的本地或远程 Logo。 */
    public function logo(string $name)
    {
        $modulePath = $this->modulePath($name);
        if ($modulePath === null) { return json(['code' => 404, 'msg' => __t('module.not_found')], 404); }
        try { $manifest = (new ModuleManifestLoader())->loadFromPath($modulePath); } catch (\InvalidArgumentException) { $manifest = null; }
        $logo = trim((string) ($manifest?->logo() ?? ''));
        if ($logo === '') { return json(['code' => 404, 'msg' => __t('module.logo.not_configured')], 404); }
        if (filter_var($logo, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($logo, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return redirect($logo);
        }
        $root = realpath($modulePath);
        $path = realpath($modulePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logo));
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
            'svg' => 'image/svg+xml', 'webp' => 'image/webp', 'ico' => 'image/x-icon'];
        $extension = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';
        if (!is_string($root) || !is_string($path) || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path) || !isset($mime[$extension])) {
            return json(['code' => 404, 'msg' => __t('module.logo.file_not_found')], 404);
        }
        return response((string) file_get_contents($path), 200, ['Content-Type' => $mime[$extension], 'Cache-Control' => 'public, max-age=86400']);
    }

    /** 执行本地模块生命周期动作。 */
    private function lifecycle(string $name, string $method, string $successKey, string $failureKey): array
    {
        if (!$this->modules->exists($name)) { error(__t('module.not_found'), null, 40102); }
        if (!$this->modules->{$method}($name)) { error(__t($failureKey), null, 40000); }
        return success(__t($successKey));
    }

    /** 在显式模块目录中查找模块。 */
    private function modulePath(string $name): ?string
    {
        foreach (config('thinkrix.modules.paths', ['Modules', 'app']) as $root) {
            $path = app()->getRootPath() . trim((string) $root, '/\\') . DIRECTORY_SEPARATOR . $name;
            if (is_dir($path)) { return $path; }
        }
        return null;
    }
}

<?php

namespace Thinkrix\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Thinkrix\Modules\Manifest\ModuleManifestLoader;
use ZipArchive;

/** 负责本地模块与项目的发布校验、打包及发布状态计算。 */
final class ModulePublishService
{
    use InteractsWithModuleMarket;

    /** 为本地模块列表附加是否可发布状态。 */
    public function withPublishState(array $modules): array
    {
        $configured = $this->registryUrl() !== '' && $this->registryAuthKey() !== '';
        foreach ($modules as &$module) {
            $module['can_publish'] = $configured && is_string($module['registry_id'] ?? null) && $module['registry_id'] !== '';
        }
        unset($module);
        return $modules;
    }

    /** 校验作者和版本后发布指定本地模块。 */
    public function publishLocal(string $name): array
    {
        $path = $this->modulePath($name);
        if ($path === null) { error(__t('module.not_found'), null, 40102); }
        $manifestObject = (new ModuleManifestLoader())->loadFromPath($path);
        if ($manifestObject === null) { error('module.json 缺少合法的 trix 节点', null, 40000); }
        $manifest = $manifestObject->toArray();
        $this->assertConfigured();
        $this->assertAuthor((string) ($manifest['author'] ?? ''));
        $this->assertVersion('modules', (string) $manifestObject->id(), (string) $manifestObject->version());
        $package = $this->zipDirectory($path, (string) $manifestObject->id());

        try {
            return $this->publishMultipart('/registry/publish/modules', $manifest, $package);
        } finally {
            @unlink($package);
        }
    }

    /** 校验根目录 trix-project.json 后发布当前项目。 */
    public function publishProject(): array
    {
        $path = app()->getRootPath() . 'trix-project.json';
        $manifest = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (!is_array($manifest) || ($manifest['schema_version'] ?? null) !== 'trix.project.v1') {
            error('根目录缺少合法的 trix-project.json', null, 40000);
        }
        foreach (['id', 'name', 'version', 'author'] as $field) {
            if (!is_string($manifest[$field] ?? null) || trim($manifest[$field]) === '') { error("项目清单 {$field} 为必填字段", null, 40000); }
        }
        $this->assertConfigured();
        $this->assertAuthor((string) $manifest['author']);
        $this->assertVersion('projects', (string) $manifest['id'], (string) $manifest['version']);
        $package = $this->zipSingle($path, 'trix-project.json', 'thinkrix-project-');
        try {
            return $this->publishMultipart('/registry/publish/projects', $manifest, $package);
        } finally {
            @unlink($package);
        }
    }

    /** 确认市场地址和 Auth Key 已配置。 */
    private function assertConfigured(): void
    {
        if ($this->registryUrl() === '' || $this->registryAuthKey() === '') { error('请先配置模块市场地址和 TRIX_AUTH_KEY', null, 40000); }
    }

    /** 作者必须匹配 Auth Key 对应用户的用户名或邮箱。 */
    private function assertAuthor(string $author): void
    {
        $me = $this->registryClient()->getJson('/registry/auth/me');
        $user = is_array($me['data']['data']['user'] ?? null) ? $me['data']['data']['user'] : [];
        $allowed = array_filter([mb_strtolower(trim((string) ($user['name'] ?? ''))), mb_strtolower(trim((string) ($user['email'] ?? '')))]);
        if (!$me['ok'] || !in_array(mb_strtolower(trim($author)), $allowed, true)) { error('清单作者必须与 Auth Key 用户名或邮箱一致', null, 40000); }
    }

    /** 本地版本必须高于市场最新版本。 */
    private function assertVersion(string $kind, string $id, string $version): void
    {
        $remote = $this->registryClient()->getJson('/registry/' . $kind . '/' . rawurlencode($id) . '/versions', [
            'page_size' => 1, 'language' => 'php', 'framework' => 'thinkphp']);
        $latest = $remote['data']['data']['items'][0]['version'] ?? $remote['data']['data']['version'] ?? null;
        if (is_string($latest) && !version_compare($version, $latest, '>')) { error("本地版本 {$version} 必须高于市场版本 {$latest}", null, 40000); }
    }

    /** 查找本地模块目录。 */
    private function modulePath(string $name): ?string
    {
        foreach (config('thinkrix.modules.paths', ['Modules', 'app']) as $root) {
            $path = app()->getRootPath() . trim((string) $root, '/\\') . DIRECTORY_SEPARATOR . $name;
            if (is_dir($path)) { return $path; }
        }
        return null;
    }

    /** 安全打包模块目录，拒绝符号链接和 VCS/依赖缓存。 */
    private function zipDirectory(string $directory, string $id): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-module-' . bin2hex(random_bytes(8)) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { error('模块发布包创建失败', null, 40000); }
        $prefix = $id . '/';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file->isLink()) { $zip->close(); @unlink($path); error('模块包含符号链接，不能发布', null, 40000); }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            if (preg_match('#(^|/)(\.git|vendor|runtime)(/|$)#', $relative)) { continue; }
            $zip->addFile($file->getPathname(), $prefix . $relative);
        }
        $zip->close();
        return $path;
    }

    /** 创建只包含单个清单的项目包。 */
    private function zipSingle(string $source, string $name, string $prefix): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(8)) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true || !$zip->addFile($source, $name) || !$zip->close()) {
            @unlink($path); error('项目发布包创建失败', null, 40000);
        }
        return $path;
    }

    /** 使用 multipart/form-data 发布清单和包。 */
    private function publishMultipart(string $endpoint, array $manifest, string $package): array
    {
        $boundary = '----TrixBoundary' . bin2hex(random_bytes(12));
        $eol = "\r\n";
        $body = '--' . $boundary . $eol . 'Content-Disposition: form-data; name="manifest"' . $eol . $eol
            . json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . $eol
            . '--' . $boundary . $eol . 'Content-Disposition: form-data; name="package"; filename="package.zip"' . $eol
            . 'Content-Type: application/zip' . $eol . $eol . file_get_contents($package) . $eol . '--' . $boundary . '--' . $eol;
        $context = stream_context_create(['http' => ['method' => 'POST', 'timeout' => (int) config('thinkrix.module_market.timeout', 30),
            'ignore_errors' => true, 'follow_location' => 0, 'max_redirects' => 0,
            'header' => "Accept: application/json\r\nAuthorization: Bearer {$this->registryAuthKey()}\r\nContent-Type: multipart/form-data; boundary={$boundary}\r\nContent-Length: " . strlen($body) . "\r\n",
            'content' => $body]]);
        $decoded = json_decode((string) @file_get_contents($this->registryUrl() . $endpoint, false, $context), true);
        if (!is_array($decoded) || ($decoded['code'] ?? -1) !== 0) { error((string) ($decoded['msg'] ?? '发布失败'), $decoded, 40000); }
        return success((string) ($decoded['msg'] ?? '已提交审核'), $decoded['data'] ?? null);
    }
}

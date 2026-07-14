<?php

namespace Thinkrix\Services;

use Thinkrix\Models\Module;
use Thinkrix\Modules\Registry\RegistryPackagePipeline;
use Thinkrix\Modules\Registry\RegistryVersionResolver;
use Thinkrix\Support\ModuleMarketTypes;

/** 负责模块市场查询、格式化、下载暂存和项目安装计划获取。 */
final class ModuleMarketService
{
    use InteractsWithModuleMarket;

    public function __construct(private readonly ModuleMarketTypes $types)
    {
    }

    public function modules(array $query): array
    {
        return success($this->fetch('/registry/modules', $query, 'module'));
    }

    public function projects(array $query): array
    {
        return success($this->fetch('/registry/projects', $query, 'project'));
    }

    public function installModule(string $id): array
    {
        $versions = $this->registryClient()->getJson('/registry/modules/' . rawurlencode($id) . '/versions', [
            'page_size' => 1, 'language' => 'php', 'framework' => 'thinkphp']);
        if (!$versions['ok']) { error((string) $versions['message'], $versions, 40000); }
        $resolved = (new RegistryVersionResolver('php', 'thinkphp'))->resolveLatest($versions['data']);
        if (!($resolved['installable'] ?? false)) { error((string) ($resolved['message'] ?? '当前模块没有可安装的 ThinkPHP adapter'), $resolved, 40000); }
        $version = (string) ($resolved['version']['version'] ?? 'latest');
        $prepared = (new RegistryPackagePipeline($this->registryClient(), signatureKey: (string) config('thinkrix.module_market.signature_key', '')))
            ->prepare((array) $resolved['adapter'], $id, $version);
        if (!$prepared['ok']) { error((string) $prepared['message'], $prepared, 40000); }

        return success('模块包已下载并暂存，请按返回命令完成本地安装', $prepared + [
            'command' => 'php think thinkrix:module-install ' . $id . ' --from-stage="' . $prepared['path'] . '" --manifest="' . $prepared['manifest'] . '" --version="' . $version . '" --target-dir="Modules/' . $this->directoryName($id) . '"']);
    }

    public function installProject(string $id): array
    {
        $versions = $this->registryClient()->getJson('/registry/projects/' . rawurlencode($id) . '/versions', [
            'page_size' => 1, 'language' => 'php', 'framework' => 'thinkphp']);
        $version = $versions['data']['data']['items'][0]['version'] ?? $versions['data']['data']['version'] ?? null;
        if (!$versions['ok'] || !is_string($version) || $version === '') { error('项目没有可安装版本。', $versions, 40000); }
        $plan = $this->registryClient()->getJson('/registry/projects/' . rawurlencode($id) . '/versions/' . rawurlencode($version) . '/install-plan', [
            'language' => 'php', 'framework' => 'thinkphp']);
        if (!$plan['ok']) { error((string) $plan['message'], $plan, 40000); }

        return success('已生成项目安装计划，请执行 thinkrix:project-install 完成安装。', $plan['data']['data'] ?? []);
    }

    private function fetch(string $endpoint, array $query, string $kind): array
    {
        $params = ['keyword' => $query['keyword'] ?? '', 'type' => $this->types->normalize($query['type'] ?? 'all'),
            'language' => $query['language'] ?? 'php', 'framework' => $query['framework'] ?? 'thinkphp',
            'page' => max(1, (int) ($query['page'] ?? 1)), 'page_size' => 16];
        $response = $this->registryClient()->getJson($endpoint, array_filter($params, static fn ($value) => $value !== ''));
        if (!$response['ok']) { return $this->emptyPage($params); }
        $data = is_array($response['data']['data'] ?? null) ? $response['data']['data'] : [];
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $installed = $kind === 'module' ? $this->installedIds() : [];
        $formatted = array_map(fn (array $item): array => $this->format($item, $kind, $installed), array_values(array_filter($items, 'is_array')));
        return ['items' => $formatted, 'page' => (int) ($data['page'] ?? $params['page']),
            'page_size' => (int) ($data['page_size'] ?? 16), 'total' => (int) ($data['total'] ?? count($formatted))];
    }

    private function format(array $item, string $kind, array $installed): array
    {
        $latest = is_array($item['latest_version'] ?? null) ? $item['latest_version'] : [];
        $id = (string) ($item['id'] ?? $item['registry_id'] ?? $item['name'] ?? '');
        $type = (string) ($item[$kind . '_type'] ?? $item['type'] ?? '-');
        $isInstalled = $kind === 'module' && isset($installed[strtolower($id)]);
        return ['id' => $id, 'title' => $item['title'] ?? $item['name'] ?? $id,
            'summary' => $item['summary'] ?? $item['description'] ?? '', 'version' => $latest['version'] ?? $item['latest_version'] ?? $item['version'] ?? '-',
            'type' => $type, 'type_label' => $this->types->label($type, $kind), 'logo' => $item['logo'] ?? null,
            'thumbnail' => $item['thumbnail'] ?? null, 'author' => $item['author'] ?? '-', 'author_url' => $item['author_url'] ?? null,
            'license' => $item['license'] ?? '-', 'downloads' => $item['downloads_count'] ?? 0, 'installed' => $isInstalled,
            'install_status' => $isInstalled ? 'installed' : 'available'];
    }

    private function installedIds(): array
    {
        $ids = [];
        foreach (Module::select() as $module) {
            foreach ([$module->registry_id ?? null, $module->name ?? null] as $id) {
                if (is_string($id) && trim($id) !== '') { $ids[strtolower(trim($id))] = true; }
            }
        }
        return $ids;
    }

    private function emptyPage(array $query): array
    {
        return ['items' => [], 'page' => (int) ($query['page'] ?? 1), 'page_size' => 16, 'total' => 0];
    }

    private function directoryName(string $id): string
    {
        return str_replace(' ', '', ucwords(str_replace(['.', '-', '_'], ' ', $id)));
    }
}

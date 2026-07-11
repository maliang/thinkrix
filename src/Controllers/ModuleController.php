<?php

namespace Thinkrix\Controllers;

use think\Request;
use think\Response;
use Thinkrix\Services\ModuleService;
use Thinkrix\Schema\Components\NaiveUI\Card;
use Thinkrix\Schema\Components\NaiveUI\Space;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Tag;
use Thinkrix\Schema\Components\NaiveUI\Result;
use Thinkrix\Schema\Components\NaiveUI\Avatar;
use Thinkrix\Schema\Components\NaiveUI\Popconfirm;
use Thinkrix\Schema\Components\NaiveUI\Modal;
use Thinkrix\Schema\Components\NaiveUI\Spin;
use Thinkrix\Schema\Components\NaiveUI\Pagination;
use Thinkrix\Schema\Components\NaiveUI\Flex;
use Thinkrix\Schema\Components\NaiveUI\Input;
use Thinkrix\Schema\Components\NaiveUI\Select;
use Thinkrix\Schema\Components\NaiveUI\TabPane;
use Thinkrix\Schema\Components\NaiveUI\Tabs;
use Thinkrix\Schema\Components\Business\DataTable;
use Thinkrix\Schema\Components\Custom\SvgIcon;
use Thinkrix\Schema\Components\Custom\Html;
use Thinkrix\Schema\Actions\SetAction;
use Thinkrix\Schema\Actions\CallAction;
use Thinkrix\Schema\Actions\FetchAction;
use Thinkrix\Models\Module;

/** 提供模块管理、模块市场、发布与安装相关的后台接口和页面结构。 */
class ModuleController extends Controller
{
    protected ModuleService $moduleService;

    /** 初始化当前对象及其依赖。 */
    public function __construct(ModuleService $moduleService)
    {
        $this->moduleService = $moduleService;
    }

    /** 返回模块管理页面结构。 */
    public function index(): array
    {
        $actionType = $this->input('action_type', 'list');
        return match ($actionType) {
            'market_ui' => $this->marketUi(),
            'installed_ui' => $this->installedUi(),
            default => $this->list(),
        };
    }

    /** 获取模块列表响应数据。 */
    protected function list(): array
    {
        $perPage = (int) $this->input('page_size', 15);
        $page = (int) $this->input('page', 1);
        $modules = $this->moduleService->getModules();
        $total = count($modules);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($modules, $offset, $perPage);

        return success([
            'list' => $items,
            'total' => $total,
            'page' => $page,
            'page_size' => $perPage,
        ]);
    }

        /** 执行 marketModules 方法对应的具体职责。 */
    public function marketModules(): array
    {
        return success($this->fetchRegistryItems('/registry/modules', [
            'keyword' => $this->input('keyword', ''),
            'type' => $this->normalizeMarketType($this->input('type', 'all')),
            'language' => $this->input('language', 'php'),
            'framework' => $this->input('framework', 'thinkphp'),
            'page' => max(1, (int) $this->input('page', 1)),
            'page_size' => 16,
        ], 'module'));
    }

        /** 执行 marketProjects 方法对应的具体职责。 */
    public function marketProjects(): array
    {
        return success($this->fetchRegistryItems('/registry/projects', [
            'keyword' => $this->input('keyword', ''),
            'type' => $this->normalizeMarketType($this->input('type', 'all')),
            'language' => $this->input('language', 'php'),
            'framework' => $this->input('framework', 'thinkphp'),
            'page' => max(1, (int) $this->input('page', 1)),
            'page_size' => 16,
        ], 'project'));
    }

        /** 启用指定模块及其运行状态。 */
    public function enable(string $name): array
    {
        if (!$this->moduleService->exists($name)) { error(__t('module.not_found'), null, 40102); }
        $result = $this->moduleService->enable($name);
        if (!$result) { error(__t('module.enable_failed'), null, 40000); }
        return success(__t('module.enable_ok'));
    }

        /** 禁用指定模块及其运行状态。 */
    public function disable(string $name): array
    {
        if (!$this->moduleService->exists($name)) { error(__t('module.not_found'), null, 40102); }
        $result = $this->moduleService->disable($name);
        if (!$result) { error(__t('module.disable_failed'), null, 40000); }
        return success(__t('module.disable_ok'));
    }

        /** 执行模块或项目安装流程。 */
    public function install(string $name): array
    {
        if (!$this->moduleService->exists($name)) { error(__t('module.not_found'), null, 40102); }
        $result = $this->moduleService->install($name);
        if (!$result) { error(__t('module.install_failed'), null, 40000); }
        return success(__t('module.install_ok'));
    }

        /** 执行模块卸载及清理流程。 */
    public function uninstall(string $name): array
    {
        if (!$this->moduleService->exists($name)) { error(__t('module.not_found'), null, 40102); }
        $result = $this->moduleService->uninstall($name);
        if (!$result) { error(__t('module.uninstall_failed'), null, 40000); }
        return success(__t('module.uninstall_ok'));
    }

    /** 获取模块 Logo 地址。 */
    public function logo(string $name)
    {
        $root = app()->getRootPath();
        $paths = config('thinkrix.modules.paths', ['Modules', 'app']);
        $modulePath = null;
        foreach ($paths as $p) {
            $candidate = $root . $p . DIRECTORY_SEPARATOR . $name;
            if (is_dir($candidate)) {
                $modulePath = $candidate;
                break;
            }
        }

        if (!$modulePath) { return json(['code' => 404, 'msg' => __t('module.message.not_found')], 404); }

        $moduleJsonPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
        if (!file_exists($moduleJsonPath)) { return json(['code' => 404, 'msg' => __t('module.config.not_found')], 404); }

        $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);
        $logoFile = $moduleJson['logo'] ?? '';
        if (empty($logoFile)) { return json(['code' => 404, 'msg' => __t('module.logo.not_configured')], 404); }

        $fullPath = $modulePath . DIRECTORY_SEPARATOR . $logoFile;
        if (!file_exists($fullPath)) { return json(['code' => 404, 'msg' => __t('module.logo.file_not_found')], 404); }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'webp' => 'image/webp', 'ico' => 'image/x-icon',
        ];
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        return response(file_get_contents($fullPath), 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

        /** 从远端服务获取并解析数据。 */
    protected function fetchRegistryItems(string $endpoint, array $query, string $type): array
    {
        $registry = rtrim((string) config('thinkrix.module_registry.url', ''), '/');
        if ($registry === '') {
            return $this->emptyRegistryPage($query);
        }

        $query = array_filter($query, static fn ($value) => $value !== '' && $value !== null);
        $url = $registry . $endpoint . '?' . http_build_query($query);
        $headers = [];
        $authKey = trim((string) config('thinkrix.module_registry.auth_key', ''));
        if ($authKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $authKey;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if (!is_string($body) || $body === '') {
            return $this->emptyRegistryPage($query);
        }

        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            return $this->emptyRegistryPage($query);
        }

        $items = $payload['data']['items'] ?? [];
        if (!is_array($items)) {
            return $this->emptyRegistryPage($query);
        }

        $installedModuleIds = $type === 'module' ? $this->installedModuleIds() : [];

        $formatted = array_values(array_map(
            fn (array $item): array => $type === 'project' ? $this->formatRegistryProject($item) : $this->formatRegistryModule($item, $installedModuleIds),
            array_filter($items, 'is_array')
        ));

        return [
            'items' => $formatted,
            'page' => (int) ($payload['data']['page'] ?? ($query['page'] ?? 1)),
            'page_size' => (int) ($payload['data']['page_size'] ?? ($query['page_size'] ?? count($formatted))),
            'total' => (int) ($payload['data']['total'] ?? count($formatted)),
        ];
    }

    /** 构造空的模块市场分页结果。 */
    protected function emptyRegistryPage(array $query): array
    {
        return [
            'items' => [],
            'page' => (int) ($query['page'] ?? 1),
            'page_size' => (int) ($query['page_size'] ?? 16),
            'total' => 0,
        ];
    }

        /** 将数据格式化为接口或页面需要的结构。 */
    protected function formatRegistryModule(array $item, array $installedModuleIds = []): array
    {
        $latest = is_array($item['latest_version'] ?? null) ? $item['latest_version'] : [];
        $latestVersion = is_string($item['latest_version'] ?? null) ? $item['latest_version'] : null;
        $id = (string) ($item['id'] ?? $item['registry_id'] ?? $item['name'] ?? '');
        $name = (string) ($item['name'] ?? '');
        $installed = $this->isRegistryModuleInstalled($id, $name, $installedModuleIds);

        return [
            'id' => $id,
            'title' => $item['title'] ?? $item['name'] ?? $item['id'] ?? '',
            'summary' => $item['summary'] ?? $item['description'] ?? '',
            'version' => $latest['version'] ?? $latestVersion ?? $item['version'] ?? '-',
            'type' => $moduleType = (string) ($item['module_type'] ?? $item['type'] ?? '-'),
            'type_label' => $this->marketTypeLabel($moduleType, 'module'),
            'logo' => $item['logo'] ?? $item['icon'] ?? null,
            'thumbnail' => $item['thumbnail'] ?? null,
            'author' => $item['author'] ?? '-',
            'author_url' => $item['author_url'] ?? null,
            'downloads' => $item['downloads_count'] ?? $item['downloads'] ?? 0,
            'license' => $item['license'] ?? '-',
            'installed' => $installed,
            'install_status' => $installed ? 'installed' : 'available',
        ];
    }

        /** 将数据格式化为接口或页面需要的结构。 */
    protected function formatRegistryProject(array $item): array
    {
        $latest = is_array($item['latest_version'] ?? null) ? $item['latest_version'] : [];
        $latestVersion = is_string($item['latest_version'] ?? null) ? $item['latest_version'] : null;

        return [
            'id' => $item['id'] ?? $item['registry_id'] ?? $item['name'] ?? '',
            'title' => $item['title'] ?? $item['name'] ?? $item['id'] ?? '',
            'summary' => $item['summary'] ?? $item['description'] ?? '',
            'version' => $latest['version'] ?? $latestVersion ?? $item['version'] ?? '-',
            'type' => $projectType = (string) ($item['project_type'] ?? $item['type'] ?? '-'),
            'type_label' => $this->marketTypeLabel($projectType, 'project'),
            'logo' => $item['logo'] ?? $item['icon'] ?? null,
            'thumbnail' => $item['thumbnail'] ?? null,
            'author' => $item['author'] ?? '-',
            'author_url' => $item['author_url'] ?? null,
            'license' => $item['license'] ?? '-',
        ];
    }

        /** 执行模块或项目安装流程。 */
    protected function installedModuleIds(): array
    {
        $ids = [];
        foreach (Module::select() as $module) {
            $this->rememberModuleId($ids, $module->name ?? null);

            $config = is_array($module->config ?? null) ? $module->config : [];
            $this->rememberModuleId($ids, $config['id'] ?? null);
            $this->rememberModuleId($ids, $config['registry_id'] ?? null);
            $this->rememberModuleId($ids, $config['trix_manifest']['id'] ?? null);
        }

        return $ids;
    }

    /** 记录模块可用于匹配安装状态的标准化标识。 */
    protected function rememberModuleId(array &$ids, mixed $value): void
    {
        if (!is_string($value) || trim($value) === '') {
            return;
        }

        $ids[strtolower(trim($value))] = true;
    }

        /** 判断当前业务条件是否成立。 */
    protected function isRegistryModuleInstalled(string $id, string $name, array $installedModuleIds): bool
    {
        foreach ([$id, $name] as $candidate) {
            $candidate = strtolower(trim($candidate));
            if ($candidate !== '' && isset($installedModuleIds[$candidate])) {
                return true;
            }
        }

        return false;
    }

        /** 执行 moduleTypeOptions 方法对应的具体职责。 */
    protected function moduleTypeOptions(): array
    {
        return [
            ['label' => '全部', 'value' => 'all'],
            ['label' => '基础能力', 'value' => 'core'],
            ['label' => '业务模块', 'value' => 'business'],
            ['label' => '外部集成', 'value' => 'integration'],
            ['label' => '界面组件', 'value' => 'ui'],
            ['label' => '开发工具', 'value' => 'tooling'],
        ];
    }

    /** 返回项目分类筛选选项。 */
    protected function projectTypeOptions(): array
    {
        return [
            ['label' => '全部', 'value' => 'all'],
            ['label' => '起步模板', 'value' => 'starter'],
            ['label' => '行业方案', 'value' => 'solution'],
            ['label' => '演示项目', 'value' => 'demo'],
            ['label' => '结构模板', 'value' => 'template'],
            ['label' => '企业工程', 'value' => 'enterprise'],
        ];
    }

        /** 执行 marketTypeLabel 方法对应的具体职责。 */
    protected function marketTypeLabel(string $type, string $kind): string
    {
        if ($type === '' || $type === '-') {
            return '-';
        }

        $options = $kind === 'project' ? $this->projectTypeOptions() : $this->moduleTypeOptions();
        foreach ($options as $option) {
            if (($option['value'] ?? null) === $type) {
                return (string) ($option['label'] ?? $type);
            }
        }

        return $type;
    }

        /** 将输入值归一化为内部标准格式。 */
    protected function normalizeMarketType(mixed $type): string
    {
        $type = is_string($type) ? trim($type) : '';

        return $type === 'all' ? '' : $type;
    }

        /** 执行 marketUi 方法对应的具体职责。 */
    protected function marketUi(): array
    {
        $schema = Card::make()->props(['title' => __t('module.market.title')])->children([
            Result::make()->props(['status' => 'info', 'title' => __t('module.market.coming_soon'), 'description' => __t('module.market.coming_soon_desc')])
                ->slot('icon', [SvgIcon::make('carbon:store')->props(['class' => 'text-6xl text-primary'])]),
        ]);
        return success($schema->toArray());
    }

        /** 执行模块或项目安装流程。 */
    protected function installedUi(): array
    {
        $routePrefix = '/' . config('thinkrix.api_prefix', 'api/admin');
        $installedLabel = $this->jsString(__t('module.tag.installed'));
        $notInstalledLabel = $this->jsString(__t('module.tag.not_installed'));
        $schema = Card::make()->props([
                'title' => __t('module.installed.title'),
                'style' => ['height' => '100%', 'display' => 'flex', 'flexDirection' => 'column'],
                'contentStyle' => ['flex' => '1 1 0%', 'overflow' => 'hidden', 'display' => 'flex', 'flexDirection' => 'column'],
            ])
            ->data(['modules' => [], 'loading' => false, 'marketLoading' => false,
                'routePrefix' => $routePrefix, 'marketVisible' => false,
                'marketActiveTab' => 'modules',
                'marketModuleKeyword' => '',
                'marketModuleType' => 'all',
                'marketProjectKeyword' => '',
                'marketProjectType' => 'all',
                'marketModuleTypeOptions' => $this->moduleTypeOptions(),
                'marketProjectTypeOptions' => $this->projectTypeOptions(),
                'marketModules' => [],
                'marketProjects' => [],
                'marketModulePage' => 1,
                'marketProjectPage' => 1,
                'marketModulePageSize' => 16,
                'marketProjectPageSize' => 16,
                'marketModuleTotal' => 0,
                'marketProjectTotal' => 0,
                'marketModuleLoading' => false,
                'marketProjectLoading' => false,
                'marketDetailVisible' => false,
                'marketDetailKind' => 'module',
                'marketDetailItem' => null,
                'marketRegistryUrl' => rtrim((string) config('thinkrix.module_registry.url', ''), '/'),
                'pagination' => ['page' => 1, 'pageSize' => 15, 'total' => 0]])
            ->methods([
                'loadData' => [
                    SetAction::make('loading', true),
                    FetchAction::make('/modules')->get()->params(['page' => '{{ pagination.page }}', 'page_size' => '{{ pagination.pageSize }}'])
                        ->then([
                            SetAction::make('modules', '{{ $response.data.list || [] }}'),
                            SetAction::make('pagination.total', '{{ $response.data.total || 0 }}'),
                        ])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "加载失败" }}'])])
                        ->finally([SetAction::make('loading', false)]),
                ],
                'handleEnable' => [FetchAction::make('/modules/{{ $event }}/enable')->put()->then([CallAction::make('$message.success', [__t('module.message.enabled')]), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "启用失败" }}'])])],
                'handleDisable' => [FetchAction::make('/modules/{{ $event }}/disable')->put()->then([CallAction::make('$message.success', [__t('module.message.disabled')]), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "禁用失败" }}'])])],
                'handleInstall' => [FetchAction::make('/modules/{{ $event }}/install')->put()->then([CallAction::make('$message.success', [__t('module.message.installed')]), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "安装失败" }}'])])],
                'handleUninstall' => [FetchAction::make('/modules/{{ $event }}/uninstall')->put()->then([CallAction::make('$message.success', [__t('module.message.uninstalled')]), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "卸载失败" }}'])])],
                'loadMarketModules' => [
                    SetAction::make('marketModuleLoading', true),
                    FetchAction::make('/modules/market/modules')->get()
                        ->params(['keyword' => '{{ marketModuleKeyword }}', 'type' => '{{ marketModuleType }}', 'language' => 'php', 'framework' => 'thinkphp', 'page' => '{{ marketModulePage }}', 'page_size' => '{{ marketModulePageSize }}'])
                        ->then([
                            SetAction::make('marketModules', '{{ $response.data.items || [] }}'),
                            SetAction::make('marketModuleTotal', '{{ $response.data.total || 0 }}'),
                            SetAction::make('marketModulePage', '{{ $response.data.page || marketModulePage }}'),
                        ])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "模块市场加载失败" }}'])])
                        ->finally([SetAction::make('marketModuleLoading', false)]),
                ],
                'loadMarketProjects' => [
                    SetAction::make('marketProjectLoading', true),
                    FetchAction::make('/modules/market/projects')->get()
                        ->params(['keyword' => '{{ marketProjectKeyword }}', 'type' => '{{ marketProjectType }}', 'language' => 'php', 'framework' => 'thinkphp', 'page' => '{{ marketProjectPage }}', 'page_size' => '{{ marketProjectPageSize }}'])
                        ->then([
                            SetAction::make('marketProjects', '{{ $response.data.items || [] }}'),
                            SetAction::make('marketProjectTotal', '{{ $response.data.total || 0 }}'),
                            SetAction::make('marketProjectPage', '{{ $response.data.page || marketProjectPage }}'),
                        ])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "项目市场加载失败" }}'])])
                        ->finally([SetAction::make('marketProjectLoading', false)]),
                ],
                'searchMarketModules' => [SetAction::make('marketModulePage', 1), CallAction::make('loadMarketModules')],
                'searchMarketProjects' => [SetAction::make('marketProjectPage', 1), CallAction::make('loadMarketProjects')],
                'handleMarketModulePageChange' => [SetAction::make('marketModulePage', '{{ $event }}'), CallAction::make('loadMarketModules')],
                'handleMarketProjectPageChange' => [SetAction::make('marketProjectPage', '{{ $event }}'), CallAction::make('loadMarketProjects')],
                'showMarketModuleDetail' => [SetAction::make('marketDetailKind', 'module'), SetAction::make('marketDetailItem', '{{ $event }}'), SetAction::make('marketDetailVisible', true)],
                'showMarketProjectDetail' => [SetAction::make('marketDetailKind', 'project'), SetAction::make('marketDetailItem', '{{ $event }}'), SetAction::make('marketDetailVisible', true)],
                'handleOpenMarket' => [SetAction::make('marketVisible', true), SetAction::make('marketModulePage', 1), SetAction::make('marketProjectPage', 1), CallAction::make('loadMarketModules'), CallAction::make('loadMarketProjects')],
                'handleCloseMarket' => [SetAction::make('marketVisible', false)],
                'handlePageChange' => [SetAction::make('pagination.page', '{{ $event }}'), CallAction::make('loadData')],
                'handlePageSizeChange' => [SetAction::make('pagination.pageSize', '{{ $event }}'), SetAction::make('pagination.page', 1), CallAction::make('loadData')],
            ])
            ->onMounted(CallAction::make('loadData'))
            ->children([
                Space::make()->props([
                    'vertical' => true,
                    'size' => 'large',
                    'wrapItem' => false,
                    'style' => ['height' => '100%', 'display' => 'flex', 'flexDirection' => 'column'],
                ])->children([
                    Space::make()->props(['justify' => 'space-between'])->children([
                        Html::div(),
                        Button::make()->type('primary')->props(['ghost' => true])->on('click', ['call' => 'handleOpenMarket'])->children([
                            SvgIcon::make('carbon:store'),
                            Html::span()->children([' 模块商城']),
                        ]),
                    ]),
                    DataTable::make()->props([
                        'loading' => '{{ loading }}',
                        'data' => '{{ modules }}',
                        'rowKey' => '{{ row => row.name }}',
                        'scrollX' => 1200,
                        'flexHeight' => true,
                        'style' => ['flex' => '1 1 0%', 'overflow' => 'hidden'],
                    ])
                        ->columns([
                            ['key' => 'logo', 'title' => 'Logo', 'width' => 60, 'slot' => [
                                Avatar::make()->if('slotData.row.logo')->props(['src' => '{{ slotData.row.logo }}', 'size' => 32, 'objectFit' => 'contain']),
                                SvgIcon::make('carbon:cube')->if('!slotData.row.logo')->props(['class' => 'text-2xl text-primary']),
                            ]],
                            ['key' => 'name', 'title' => __t('module.column.name'), 'width' => 150],
                            ['key' => 'version', 'title' => __t('module.column.version'), 'width' => 80],
                            ['key' => 'description', 'title' => __t('module.column.description'), 'ellipsis' => true],
                            ['key' => 'author', 'title' => __t('module.column.author'), 'width' => 100],
                            ['key' => 'website', 'title' => __t('module.column.website'), 'width' => 120, 'ellipsis' => true, 'slot' => [Button::make()->if('slotData.row.website')->size('small')->props(['text' => true, 'type' => 'primary', 'tag' => 'a', 'href' => '{{ slotData.row.website }}', 'target' => '_blank'])->children([__t('module.button.visit')])]],
                            ['key' => 'enabled', 'title' => __t('module.column.status'), 'width' => 80, 'slot' => [Tag::make()->props(['type' => "{{ slotData.row.enabled ? 'success' : 'default' }}", 'size' => 'small'])->children(["{{ slotData.row.enabled ? {$installedLabel} : {$notInstalledLabel} }}"])]],
                            ['key' => 'actions', 'title' => __t('module.column.actions'), 'width' => 160, 'slot' => [
                                Space::make()->children([
                                    // 未安装：显示安装按钮
                                    Button::make()->if('!slotData.row.enabled')->size('small')->type('primary')->props(['text' => true])->on('click', ['call' => 'handleInstall', 'args' => ['{{ slotData.row.name }}']])->text(__t('module.button.install')),
                                    // 已安装：显示禁用和卸载
                                    Button::make()->if('slotData.row.enabled')->size('small')->type('warning')->props(['text' => true])->on('click', ['call' => 'handleDisable', 'args' => ['{{ slotData.row.name }}']])->text(__t('ui.tag.disabled')),
                                    Popconfirm::make()->if('slotData.row.enabled')->on('positive-click', ['call' => 'handleUninstall', 'args' => ['{{ slotData.row.name }}']])->slot('trigger', [Button::make()->size('small')->type('error')->props(['text' => true])->text(__t('module.button.uninstall'))])->children(['确定卸载该模块？将删除菜单和权限，并回滚数据库迁移。']),
                                ]),
                            ]],
                        ]),
                    Flex::make()->if('pagination.total > pagination.pageSize')->props(['justify' => 'end'])->children([
                        Pagination::make()->props([
                            'page' => '{{ pagination.page }}', 'pageSize' => '{{ pagination.pageSize }}',
                            'itemCount' => '{{ pagination.total }}', 'showSizePicker' => true,
                            'pageSizes' => [10, 15, 20, 50],
                            'onUpdate:page' => ['call' => 'handlePageChange'],
                            'onUpdate:pageSize' => ['call' => 'handlePageSizeChange'],
                        ]),
                    ]),
                ]),
                Modal::make()->props(['show' => '{{ marketVisible }}', 'title' => __t('module.market.button'), 'style' => ['width' => '1080px', 'height' => '760px'], 'preset' => 'card', 'content-style' => ['height' => '682px', 'padding' => '16px 20px 12px', 'overflow' => 'hidden', 'boxSizing' => 'border-box']])
                    ->on('update:show', ['call' => 'handleCloseMarket'])
                    ->children([
                        Tabs::make()
                            ->type('line')
                            ->props(['style' => ['height' => '100%', 'display' => 'flex', 'flexDirection' => 'column']])
                            ->model(['value' => 'marketActiveTab'])
                            ->children([
                                TabPane::make()->name('modules')->tab('模块')->children($this->marketModulesPane()),
                                TabPane::make()->name('projects')->tab('项目')->children($this->marketProjectsPane()),
                            ]),
                    ]),
                $this->marketDetailModal(),
            ]);
        return success($schema->toArray());
    }

        /** 执行 marketModulesPaneTableOld 方法对应的具体职责。 */
    protected function marketModulesPaneTableOld(): array
    {
        return [
            Space::make()->props(['style' => 'margin-bottom: 12px'])->children([
                Select::make()
                    ->model(['value' => 'marketModuleType'])
                    ->props([
                        'placeholder' => '全部模块分类',
                        'clearable' => true,
                        'options' => '{{ marketModuleTypeOptions }}',
                        'style' => ['width' => '160px'],
                    ]),
                Input::make()
                    ->model(['value' => 'marketModuleKeyword'])
                    ->props([
                        'placeholder' => '搜索模块名称、ID 或描述',
                        'clearable' => true,
                        'style' => ['width' => '280px'],
                    ]),
                Button::make()->type('primary')->on('click', ['call' => 'loadMarketModules'])->text('搜索'),
            ]),
            DataTable::make()->props([
                'loading' => '{{ marketModuleLoading }}',
                'data' => '{{ marketModules }}',
                'rowKey' => '{{ row => row.id }}',
                'scrollX' => 900,
            ])->columns([
                ['key' => 'logo', 'title' => 'Logo', 'width' => 64, 'slot' => [
                    Avatar::make()->if('slotData.row.logo')->props(['src' => '{{ slotData.row.logo }}', 'size' => 32, 'objectFit' => 'contain']),
                    SvgIcon::make('carbon:cube')->if('!slotData.row.logo')->props(['class' => 'text-xl text-primary']),
                ]],
                ['key' => 'id', 'title' => '模块 ID', 'width' => 180],
                ['key' => 'title', 'title' => '名称', 'width' => 160],
                ['key' => 'author', 'title' => '作者', 'width' => 120],
                ['key' => 'summary', 'title' => '描述', 'ellipsis' => true],
                ['key' => 'version', 'title' => '版本', 'width' => 90],
                ['key' => 'type', 'title' => '分类', 'width' => 90],
                ['key' => 'install_status', 'title' => '状态', 'width' => 90, 'slot' => [
                    Tag::make()
                        ->props(['type' => "{{ slotData.row.installed ? 'success' : 'default' }}", 'size' => 'small'])
                        ->children(['{{ slotData.row.installed ? "已安装" : "可安装" }}']),
                ]],
                ['key' => 'actions', 'title' => '操作', 'width' => 100, 'slot' => [
                    Button::make()
                        ->if('!slotData.row.installed')
                        ->size('small')
                        ->type('primary')
                        ->props(['text' => true])
                        ->on('click', CallAction::make('$message.info', ['{{ "执行：php think thinkrix:module-install " + slotData.row.id + " --registry=" + marketRegistryUrl }}']))
                        ->text('安装'),
                    Button::make()
                        ->if('slotData.row.installed')
                        ->size('small')
                        ->props(['text' => true, 'disabled' => true])
                        ->text('已安装'),
                ]],
            ]),
        ];
    }

        /** 执行 marketProjectsPaneTableOld 方法对应的具体职责。 */
    protected function marketProjectsPaneTableOld(): array
    {
        return [
            Space::make()->props(['style' => 'margin-bottom: 12px'])->children([
                Select::make()
                    ->model(['value' => 'marketProjectType'])
                    ->props([
                        'placeholder' => '全部项目分类',
                        'clearable' => true,
                        'options' => '{{ marketProjectTypeOptions }}',
                        'style' => ['width' => '160px'],
                    ]),
                Input::make()
                    ->model(['value' => 'marketProjectKeyword'])
                    ->props([
                        'placeholder' => '搜索项目名称、ID 或描述',
                        'clearable' => true,
                        'style' => ['width' => '280px'],
                    ]),
                Button::make()->type('primary')->on('click', ['call' => 'loadMarketProjects'])->text('搜索'),
            ]),
            DataTable::make()->props([
                'loading' => '{{ marketProjectLoading }}',
                'data' => '{{ marketProjects }}',
                'rowKey' => '{{ row => row.id }}',
                'scrollX' => 900,
            ])->columns([
                ['key' => 'logo', 'title' => 'Logo', 'width' => 64, 'slot' => [
                    Avatar::make()->if('slotData.row.logo')->props(['src' => '{{ slotData.row.logo }}', 'size' => 32, 'objectFit' => 'contain']),
                    SvgIcon::make('carbon:template')->if('!slotData.row.logo')->props(['class' => 'text-xl text-primary']),
                ]],
                ['key' => 'id', 'title' => '项目 ID', 'width' => 180],
                ['key' => 'title', 'title' => '名称', 'width' => 160],
                ['key' => 'author', 'title' => '作者', 'width' => 120],
                ['key' => 'summary', 'title' => '描述', 'ellipsis' => true],
                ['key' => 'version', 'title' => '版本', 'width' => 90],
                ['key' => 'type', 'title' => '分类', 'width' => 90],
                ['key' => 'license', 'title' => '许可', 'width' => 90],
                ['key' => 'actions', 'title' => '操作', 'width' => 120, 'slot' => [
                    Button::make()
                        ->size('small')
                        ->type('primary')
                        ->props(['text' => true])
                        ->on('click', CallAction::make('$message.info', ['项目安装计划即将接入：{{ slotData.row.id }}']))
                        ->text('查看'),
                ]],
            ]),
        ];
    }

        /** 执行 marketModulesPane 方法对应的具体职责。 */
    protected function marketModulesPane(): array
    {
        return $this->marketPane('marketModules', 'marketModuleType', 'marketModuleKeyword', 'marketModuleTypeOptions', 'searchMarketModules', 'module', 'marketModulePage', 'marketModulePageSize', 'marketModuleTotal', 'handleMarketModulePageChange');
    }

        /** 执行 marketProjectsPane 方法对应的具体职责。 */
    protected function marketProjectsPane(): array
    {
        return $this->marketPane('marketProjects', 'marketProjectType', 'marketProjectKeyword', 'marketProjectTypeOptions', 'searchMarketProjects', 'project', 'marketProjectPage', 'marketProjectPageSize', 'marketProjectTotal', 'handleMarketProjectPageChange');
    }

        /** 执行 marketPane 方法对应的具体职责。 */
    protected function marketPane(string $itemsPath, string $typePath, string $keywordPath, string $optionsPath, string $searchMethod, string $kind, string $pagePath, string $pageSizePath, string $totalPath, string $pageMethod): array
    {
        return [
            Flex::make()->vertical()->props(['style' => ['height' => '626px', 'overflow' => 'hidden']])->children([
                Space::make()->props(['style' => 'margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #eef2f6;'])->children([
                    Select::make()->model(['value' => $typePath])->props([
                        'placeholder' => $kind === 'project' ? '全部项目分类' : '全部模块分类',
                        'clearable' => false,
                        'options' => "{{ {$optionsPath} }}",
                        'style' => ['width' => '160px'],
                    ]),
                    Input::make()->model(['value' => $keywordPath])->props([
                        'placeholder' => $kind === 'project' ? '搜索项目名称、ID 或描述' : '搜索模块名称、ID 或描述',
                        'clearable' => true,
                        'style' => ['width' => '280px'],
                    ]),
                    Button::make()->type('primary')->on('click', ['call' => $searchMethod])->text('搜索'),
                ]),
                $this->marketCardGrid($itemsPath, $kind),
                $this->marketPagination($pagePath, $pageSizePath, $totalPath, $pageMethod),
            ]),
        ];
    }

        /** 执行 marketCardGrid 方法对应的具体职责。 */
    protected function marketCardGrid(string $itemsPath, string $kind): Html
    {
        $detailMethod = $kind === 'project' ? 'showMarketProjectDetail' : 'showMarketModuleDetail';
        $emptyText = $kind === 'project' ? '暂无匹配项目' : '暂无匹配模块';
        $icon = $kind === 'project' ? 'carbon:template' : 'carbon:cube';

        return Html::div()->props(['style' => [
            'flex' => '1 1 0%',
            'overflowY' => 'auto',
            'display' => 'grid',
            'gridTemplateColumns' => 'repeat(4, minmax(0, 1fr))',
            'gridAutoRows' => '116px',
            'gap' => '10px',
            'alignContent' => 'start',
            'padding' => '2px 4px 2px 0',
        ]])->children([
            Card::make()
                ->for("item in {$itemsPath}", 'item.id')
                ->hoverable()
                ->bordered(true)
                ->size('small')
                ->props([
                    'style' => ['height' => '116px', 'cursor' => 'pointer', 'borderColor' => '#e5e7eb', 'background' => '#ffffff'],
                    'content-style' => ['height' => '100%', 'padding' => '10px 12px', 'boxSizing' => 'border-box'],
                ])
                ->on('click', ['call' => $detailMethod, 'args' => ['{{ item }}']])
                ->children([
                    Flex::make()->props(['align' => 'center', 'style' => ['gap' => '10px', 'marginBottom' => '7px']])->children([
                        Avatar::make()->if('item.logo')->props(['src' => '{{ item.logo }}', 'size' => 36, 'objectFit' => 'contain', 'style' => ['background' => '#f8fafc', 'border' => '1px solid #eef2f6']]),
                        SvgIcon::make($icon)->if('!item.logo')->props(['class' => 'text-2xl text-primary']),
                        Html::div()->props(['style' => ['minWidth' => 0, 'flex' => 1]])->children([
                            Html::div()->props(['style' => ['fontWeight' => 600, 'fontSize' => '14px', 'lineHeight' => '20px', 'whiteSpace' => 'nowrap', 'overflow' => 'hidden', 'textOverflow' => 'ellipsis']])->children(['{{ item.title }}']),
                            Html::div()->props(['style' => ['fontSize' => '12px', 'lineHeight' => '18px', 'color' => '#667085', 'whiteSpace' => 'nowrap', 'overflow' => 'hidden', 'textOverflow' => 'ellipsis']])->children(['{{ item.id }}']),
                        ]),
                    ]),
                    Html::div()->props(['style' => ['height' => '38px', 'fontSize' => '12px', 'lineHeight' => '19px', 'color' => '#475467', 'overflow' => 'hidden']])->children(['{{ item.summary || "暂无简介" }}']),
                    Flex::make()->props(['justify' => 'space-between', 'align' => 'center', 'style' => ['marginTop' => '8px']])->children([
                        Tag::make()->props(['size' => 'small', 'bordered' => false])->children(['{{ item.type_label || item.type || "-" }}']),
                        Tag::make()->props(['size' => 'small', 'type' => '{{ item.installed ? "success" : "default" }}'])->children(['{{ item.installed ? "已安装" : item.version }}']),
                    ]),
                ]),
            Html::div()->if("!{$itemsPath} || {$itemsPath}.length === 0")->props(['style' => ['gridColumn' => '1 / -1', 'height' => '260px', 'display' => 'flex', 'alignItems' => 'center', 'justifyContent' => 'center', 'color' => '#667085']])->children([$emptyText]),
        ]);
    }

        /** 执行 marketPagination 方法对应的具体职责。 */
    protected function marketPagination(string $pagePath, string $pageSizePath, string $totalPath, string $handler): Flex
    {
        return Flex::make()->props(['justify' => 'end', 'align' => 'center', 'style' => ['height' => '48px', 'flex' => '0 0 48px', 'paddingTop' => '10px', 'borderTop' => '1px solid #e5e7eb', 'boxSizing' => 'border-box', 'background' => '#fff']])->children([
            Pagination::make()
                ->props([
                    'page' => "{{ {$pagePath} }}",
                    'pageSize' => "{{ {$pageSizePath} }}",
                    'itemCount' => "{{ {$totalPath} }}",
                    'showSizePicker' => false,
                ])
                ->on('update:page', ['call' => $handler, 'args' => ['{{ $event }}']]),
        ]);
    }

        /** 执行 marketDetailModal 方法对应的具体职责。 */
    protected function marketDetailModal(): Modal
    {
        return Modal::make()->props(['show' => '{{ marketDetailVisible }}', 'title' => '{{ marketDetailKind === "project" ? "项目详情" : "模块详情" }}', 'style' => ['width' => '720px'], 'preset' => 'card'])
            ->on('update:show', [SetAction::make('marketDetailVisible', '{{ $event }}')])
            ->children([
                Flex::make()->vertical()->props(['style' => ['gap' => '14px']])->children([
                    Flex::make()->props(['align' => 'center', 'style' => ['gap' => '12px']])->children([
                        Avatar::make()->if('marketDetailItem?.logo')->props(['src' => '{{ marketDetailItem.logo }}', 'size' => 48, 'objectFit' => 'contain']),
                        SvgIcon::make('carbon:cube')->if('!marketDetailItem?.logo')->props(['class' => 'text-4xl text-primary']),
                        Html::div()->children([
                            Html::div()->props(['style' => ['fontSize' => '18px', 'fontWeight' => 700]])->children(['{{ marketDetailItem?.title || "-" }}']),
                            Html::div()->props(['style' => ['fontSize' => '12px', 'color' => '#667085']])->children(['{{ marketDetailItem?.id || "-" }}']),
                        ]),
                    ]),
                    Html::div()->props(['style' => ['lineHeight' => '22px', 'color' => '#344054']])->children(['{{ marketDetailItem?.summary || "暂无简介" }}']),
                    Space::make()->props(['wrap' => true])->children([
                        Tag::make()->children(['{{ marketDetailItem?.type_label || marketDetailItem?.type || "-" }}']),
                        Tag::make()->children(['版本 {{ marketDetailItem?.version || "-" }}']),
                        Tag::make()->children(['{{ marketDetailItem?.license || "-" }}']),
                        Tag::make()->if('marketDetailItem?.author')->children(['{{ marketDetailItem.author }}']),
                    ]),
                    Html::div()->props(['style' => ['fontSize' => '12px', 'color' => '#667085']])->children(['{{ marketDetailKind === "project" ? "项目可作为多个模块的组合安装入口。" : "模块安装请在本地命令行执行对应安装命令。" }}']),
                    Space::make()->props(['justify' => 'end'])->children([
                        Button::make()->on('click', SetAction::make('marketDetailVisible', false))->text('关闭'),
                        Button::make()->if('marketDetailKind === "module" && !marketDetailItem?.installed')->type('primary')->on('click', CallAction::make('$message.info', ['{{ "执行：php think thinkrix:module-install " + marketDetailItem.id + " --registry=" + marketRegistryUrl }}']))->text('安装命令'),
                    ]),
                ]),
            ]);
    }
}

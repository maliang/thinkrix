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
use Thinkrix\Schema\Components\Business\DataTable;
use Thinkrix\Schema\Components\Custom\SvgIcon;
use Thinkrix\Schema\Components\Custom\Html;
use Thinkrix\Schema\Actions\SetAction;
use Thinkrix\Schema\Actions\CallAction;
use Thinkrix\Schema\Actions\FetchAction;

class ModuleController extends Controller
{
    protected ModuleService $moduleService;

    public function __construct(ModuleService $moduleService)
    {
        $this->moduleService = $moduleService;
    }

    public function index(): array
    {
        $actionType = $this->input('action_type', 'list');
        return match ($actionType) {
            'market_ui' => $this->marketUi(),
            'installed_ui' => $this->installedUi(),
            default => $this->list(),
        };
    }

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

    public function enable(string $name): array
    {
        if (!$this->moduleService->exists($name)) { error('模块不存在', null, 40102); }
        $result = $this->moduleService->enable($name);
        if (!$result) { error('启用失败', null, 40000); }
        return success('启用成功');
    }

    public function disable(string $name): array
    {
        if (!$this->moduleService->exists($name)) { error('模块不存在', null, 40102); }
        $result = $this->moduleService->disable($name);
        if (!$result) { error('禁用失败', null, 40000); }
        return success('禁用成功');
    }

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

        if (!$modulePath) { return json(['code' => 404, 'msg' => '模块不存在'], 404); }

        $moduleJsonPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
        if (!file_exists($moduleJsonPath)) { return json(['code' => 404, 'msg' => '模块配置不存在'], 404); }

        $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);
        $logoFile = $moduleJson['logo'] ?? '';
        if (empty($logoFile)) { return json(['code' => 404, 'msg' => 'Logo 未配置'], 404); }

        $fullPath = $modulePath . DIRECTORY_SEPARATOR . $logoFile;
        if (!file_exists($fullPath)) { return json(['code' => 404, 'msg' => 'Logo 文件不存在'], 404); }

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

    protected function marketUi(): array
    {
        $schema = Card::make()->props(['title' => '模块市场'])->children([
            Result::make()->props(['status' => 'info', 'title' => '敬请期待', 'description' => '模块市场正在开发中，即将上线...'])
                ->slot('icon', [SvgIcon::make('carbon:store')->props(['class' => 'text-6xl text-primary'])]),
        ]);
        return success($schema->toArray());
    }

    protected function installedUi(): array
    {
        $routePrefix = '/' . config('thinkrix.api_prefix', 'api/admin');
        $schema = Card::make()->props([
                'title' => '已安装模块',
                'style' => ['height' => '100%', 'display' => 'flex', 'flexDirection' => 'column'],
                'contentStyle' => ['flex' => '1 1 0%', 'overflow' => 'hidden', 'display' => 'flex', 'flexDirection' => 'column'],
            ])
            ->data(['modules' => [], 'loading' => false, 'marketLoading' => false,
                'routePrefix' => $routePrefix, 'marketVisible' => false,
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
                'handleEnable' => [FetchAction::make('/modules/{{ $event }}/enable')->put()->then([CallAction::make('$message.success', ['启用成功']), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "启用失败" }}'])])],
                'handleDisable' => [FetchAction::make('/modules/{{ $event }}/disable')->put()->then([CallAction::make('$message.success', ['禁用成功']), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "禁用失败" }}'])])],
                'handleOpenMarket' => [SetAction::make('marketVisible', true)],
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
                                Avatar::make()->if('slotData.row.logo')->props(['src' => '{{ routePrefix + "/modules/" + slotData.row.name + "/logo" }}', 'size' => 32, 'objectFit' => 'contain']),
                                SvgIcon::make('carbon:cube')->if('!slotData.row.logo')->props(['class' => 'text-2xl text-primary']),
                            ]],
                            ['key' => 'name', 'title' => '模块名称', 'width' => 150],
                            ['key' => 'version', 'title' => '版本', 'width' => 80],
                            ['key' => 'description', 'title' => '描述', 'ellipsis' => true],
                            ['key' => 'author', 'title' => '作者', 'width' => 100],
                            ['key' => 'website', 'title' => '网址', 'width' => 120, 'ellipsis' => true, 'slot' => [Button::make()->if('slotData.row.website')->size('small')->props(['text' => true, 'type' => 'primary', 'tag' => 'a', 'href' => '{{ slotData.row.website }}', 'target' => '_blank'])->children(['访问'])]],
                            ['key' => 'enabled', 'title' => '状态', 'width' => 80, 'slot' => [Tag::make()->props(['type' => "{{ slotData.row.enabled ? 'success' : 'default' }}", 'size' => 'small'])->children(["{{ slotData.row.enabled ? '已启用' : '已禁用' }}"])]],
                            ['key' => 'actions', 'title' => '操作', 'width' => 120, 'slot' => [
                                Space::make()->children([
                                    Button::make()->if('!slotData.row.enabled')->size('small')->type('primary')->props(['text' => true])->on('click', ['call' => 'handleEnable', 'args' => ['{{ slotData.row.name }}']])->text('启用'),
                                    Popconfirm::make()->if('slotData.row.enabled')->on('positive-click', ['call' => 'handleDisable', 'args' => ['{{ slotData.row.name }}']])->slot('trigger', [Button::make()->size('small')->type('warning')->props(['text' => true])->text('禁用')])->children(['确定禁用该模块？']),
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
                Modal::make()->props(['show' => '{{ marketVisible }}', 'title' => '模块商城', 'style' => 'width: 800px', 'preset' => 'card'])
                    ->on('update:show', ['call' => 'handleCloseMarket'])
                    ->children([
                        Spin::make()->props(['show' => '{{ marketLoading }}'])->children([
                            Result::make()->props(['status' => 'info', 'title' => '敬请期待', 'description' => '模块市场正在开发中，即将上线...'])
                                ->slot('icon', [SvgIcon::make('carbon:store')->props(['class' => 'text-6xl text-primary'])]),
                        ]),
                    ]),
            ]);
        return success($schema->toArray());
    }
}

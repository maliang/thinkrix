<?php

namespace Thinkrix\Schema\Pages;

use Thinkrix\Schema\Actions\CallAction;
use Thinkrix\Schema\Actions\FetchAction;
use Thinkrix\Schema\Actions\SetAction;
use Thinkrix\Schema\Components\Business\DataTable;
use Thinkrix\Schema\Components\Custom\SvgIcon;
use Thinkrix\Schema\Components\NaiveUI\Avatar;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Card;
use Thinkrix\Schema\Components\NaiveUI\Flex;
use Thinkrix\Schema\Components\NaiveUI\Pagination;
use Thinkrix\Schema\Components\NaiveUI\Popconfirm;
use Thinkrix\Schema\Components\NaiveUI\Space;
use Thinkrix\Schema\Components\NaiveUI\Tag;

/** 构建已安装模块页面并组合模块市场弹窗。 */
final class ModuleManagementSchema
{
    public function __construct(private readonly ModuleMarketSchema $market) {}

    public function installed(): array
    {
        $schema = Card::make()->props(['title' => __t('module.installed.title'),
            'style' => ['height' => '100%', 'display' => 'flex', 'flexDirection' => 'column'],
            'contentStyle' => ['flex' => '1 1 0%', 'overflow' => 'hidden', 'display' => 'flex', 'flexDirection' => 'column']])
            ->data($this->data())->methods($this->methods())->onMounted(CallAction::make('loadData'))->children([
                Space::make()->props(['justify' => 'end', 'style' => 'margin-bottom: 16px'])->children([
                    Button::make()->type('info')->on('click', ['call' => 'handlePublishProject'])->text('上传当前项目'),
                    Button::make()->type('primary')->on('click', ['call' => 'openModuleMarket'])->text('模块市场'),
                ]),
                $this->table(),
                Flex::make()->if('pagination.total > pagination.pageSize')->props(['justify' => 'end'])->children([
                    Pagination::make()->props(['page' => '{{ pagination.page }}', 'pageSize' => '{{ pagination.pageSize }}',
                        'itemCount' => '{{ pagination.total }}', 'showSizePicker' => true, 'pageSizes' => [10, 15, 20, 50]])
                        ->on('update:page', ['call' => 'handlePageChange', 'args' => ['{{ $event }}']])
                        ->on('update:pageSize', ['call' => 'handlePageSizeChange', 'args' => ['{{ $event }}']]),
                ]),
                $this->market->modal(), $this->market->detailModal(),
            ]);
        return success($schema->toArray());
    }

    /** 返回页面稳定初始状态。 */
    private function data(): array
    {
        return ['modules' => [], 'loading' => false, 'pagination' => ['page' => 1, 'pageSize' => 15, 'total' => 0],
            'marketVisible' => false, 'marketActiveTab' => 'modules', 'marketModuleKeyword' => '', 'marketModuleType' => 'all',
            'marketProjectKeyword' => '', 'marketProjectType' => 'all', 'marketModuleTypeOptions' => $this->market->moduleTypeOptions(),
            'marketProjectTypeOptions' => $this->market->projectTypeOptions(), 'marketModules' => [], 'marketProjects' => [],
            'marketModulePage' => 1, 'marketProjectPage' => 1, 'marketModulePageSize' => 16, 'marketProjectPageSize' => 16,
            'marketModuleTotal' => 0, 'marketProjectTotal' => 0, 'marketModuleLoading' => false, 'marketProjectLoading' => false,
            'marketDetailVisible' => false, 'marketDetailKind' => 'module', 'marketDetailItem' => null];
    }

    /** 返回页面行为，所有市场请求固定携带 php/thinkphp。 */
    private function methods(): array
    {
        return [
            'loadData' => [SetAction::make('loading', true), FetchAction::make('/modules')->get()->params(['page' => '{{ pagination.page }}', 'page_size' => '{{ pagination.pageSize }}'])
                ->then([SetAction::make('modules', '{{ $response.data.list || [] }}'), SetAction::make('pagination.total', '{{ $response.data.total || 0 }}')])
                ->catch([CallAction::make('$message.error', ['{{ $error.message || "加载失败" }}'])])->finally([SetAction::make('loading', false)]),],
            'openModuleMarket' => [SetAction::make('marketVisible', true), SetAction::make('marketModulePage', 1), SetAction::make('marketProjectPage', 1), CallAction::make('loadMarketModules'), CallAction::make('loadMarketProjects')],
            'loadMarketModules' => [SetAction::make('marketModuleLoading', true), FetchAction::make('/modules/market/modules')->get()
                ->params(['keyword' => '{{ marketModuleKeyword }}', 'type' => '{{ marketModuleType }}', 'language' => 'php', 'framework' => 'thinkphp', 'page' => '{{ marketModulePage }}', 'page_size' => 16])
                ->then([SetAction::make('marketModules', '{{ $response.data.items || [] }}'), SetAction::make('marketModuleTotal', '{{ $response.data.total || 0 }}'), SetAction::make('marketModulePage', '{{ $response.data.page || marketModulePage }}')])
                ->catch([CallAction::make('$message.error', ['{{ $error.message || "模块市场加载失败" }}'])])->finally([SetAction::make('marketModuleLoading', false)]),],
            'loadMarketProjects' => [SetAction::make('marketProjectLoading', true), FetchAction::make('/modules/market/projects')->get()
                ->params(['keyword' => '{{ marketProjectKeyword }}', 'type' => '{{ marketProjectType }}', 'language' => 'php', 'framework' => 'thinkphp', 'page' => '{{ marketProjectPage }}', 'page_size' => 16])
                ->then([SetAction::make('marketProjects', '{{ $response.data.items || [] }}'), SetAction::make('marketProjectTotal', '{{ $response.data.total || 0 }}'), SetAction::make('marketProjectPage', '{{ $response.data.page || marketProjectPage }}')])
                ->catch([CallAction::make('$message.error', ['{{ $error.message || "项目市场加载失败" }}'])])->finally([SetAction::make('marketProjectLoading', false)]),],
            'searchMarketModules' => [SetAction::make('marketModulePage', 1), CallAction::make('loadMarketModules')],
            'searchMarketProjects' => [SetAction::make('marketProjectPage', 1), CallAction::make('loadMarketProjects')],
            'handleMarketModulePageChange' => [SetAction::make('marketModulePage', '{{ $event }}'), CallAction::make('loadMarketModules')],
            'handleMarketProjectPageChange' => [SetAction::make('marketProjectPage', '{{ $event }}'), CallAction::make('loadMarketProjects')],
            'showMarketModuleDetail' => [SetAction::make('marketDetailKind', 'module'), SetAction::make('marketDetailItem', '{{ $event }}'), SetAction::make('marketDetailVisible', true)],
            'showMarketProjectDetail' => [SetAction::make('marketDetailKind', 'project'), SetAction::make('marketDetailItem', '{{ $event }}'), SetAction::make('marketDetailVisible', true)],
            'handleEnable' => $this->action('put', '/modules/{{ $event }}/enable', '已启用'),
            'handleDisable' => $this->action('put', '/modules/{{ $event }}/disable', '已禁用'),
            'handleInstall' => $this->action('put', '/modules/{{ $event }}/install', '已安装'),
            'handleUninstall' => $this->action('put', '/modules/{{ $event }}/uninstall', '已卸载'),
            'handlePublishModule' => $this->action('post', '/modules/{{ $event }}/publish', '已提交审核', false),
            'handlePublishProject' => $this->action('post', '/modules/projects/publish', '项目已提交审核', false),
            'handleInstallMarketModule' => $this->action('post', '/modules/market/modules/{{ marketDetailItem.id }}/install', '模块包已下载并暂存', false),
            'handleInstallMarketProject' => $this->action('post', '/modules/market/projects/{{ marketDetailItem.id }}/install', '已获取项目安装计划', false),
            'handlePageChange' => [SetAction::make('pagination.page', '{{ $event }}'), CallAction::make('loadData')],
            'handlePageSizeChange' => [SetAction::make('pagination.pageSize', '{{ $event }}'), SetAction::make('pagination.page', 1), CallAction::make('loadData')],
        ];
    }

    /** 构造通用 Fetch 行为。 */
    private function action(string $method, string $url, string $message, bool $reload = true): array
    {
        $fetch = $method === 'put' ? FetchAction::make($url)->put() : FetchAction::make($url)->post();
        $then = [CallAction::make('$message.success', [$message])];
        if ($reload) { $then[] = CallAction::make('loadData'); }
        return [$fetch->then($then)->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])];
    }

    /** 构建本地模块表格。 */
    private function table(): DataTable
    {
        return DataTable::make()->props(['loading' => '{{ loading }}', 'data' => '{{ modules }}', 'rowKey' => '{{ row => row.name }}',
            'scrollX' => 1200, 'flexHeight' => true, 'style' => ['flex' => '1 1 0%', 'overflow' => 'hidden']])->columns([
                ['key' => 'logo', 'title' => 'Logo', 'width' => 60, 'slot' => [Avatar::make()->if('slotData.row.logo')->props(['src' => '{{ slotData.row.logo }}', 'size' => 32, 'objectFit' => 'contain']), SvgIcon::make('carbon:cube')->if('!slotData.row.logo')]],
                ['key' => 'name', 'title' => __t('module.column.name'), 'width' => 150], ['key' => 'version', 'title' => __t('module.column.version'), 'width' => 90],
                ['key' => 'description', 'title' => __t('module.column.description'), 'ellipsis' => true], ['key' => 'author', 'title' => __t('module.column.author'), 'width' => 100],
                ['key' => 'enabled', 'title' => __t('module.column.status'), 'width' => 90, 'slot' => [Tag::make()->props(['type' => '{{ slotData.row.enabled ? "success" : "default" }}'])->children(['{{ slotData.row.enabled ? "已启用" : "已禁用" }}'])]],
                ['key' => 'actions', 'title' => __t('module.column.actions'), 'width' => 250, 'slot' => [Space::make()->children([
                    Button::make()->if('slotData.row.can_publish')->size('small')->type('primary')->props(['text' => true])->on('click', ['call' => 'handlePublishModule', 'args' => ['{{ slotData.row.name }}']])->text('上传'),
                    Button::make()->if('!slotData.row.enabled')->size('small')->type('primary')->props(['text' => true])->on('click', ['call' => 'handleEnable', 'args' => ['{{ slotData.row.name }}']])->text('启用'),
                    Button::make()->if('slotData.row.enabled')->size('small')->type('warning')->props(['text' => true])->on('click', ['call' => 'handleDisable', 'args' => ['{{ slotData.row.name }}']])->text('禁用'),
                    Popconfirm::make()->on('positive-click', ['call' => 'handleUninstall', 'args' => ['{{ slotData.row.name }}']])->slot('trigger', [Button::make()->size('small')->type('error')->props(['text' => true])->text('卸载')])->children(['确定卸载该模块？']),
                ])]],
            ]);
    }
}

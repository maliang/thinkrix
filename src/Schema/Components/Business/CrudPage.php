<?php

namespace Thinkrix\Schema\Components\Business;

use Thinkrix\Schema\Components\Component;
use Thinkrix\Schema\Components\NaiveUI\Card;
use Thinkrix\Schema\Components\NaiveUI\Form;
use Thinkrix\Schema\Components\NaiveUI\FormItem;
use Thinkrix\Schema\Components\NaiveUI\Input;
use Thinkrix\Schema\Components\NaiveUI\Select;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Space;
use Thinkrix\Schema\Components\NaiveUI\Modal;
use Thinkrix\Schema\Components\NaiveUI\Drawer;
use Thinkrix\Schema\Components\NaiveUI\DrawerContent;
use Thinkrix\Schema\Components\NaiveUI\Flex;
use Thinkrix\Schema\Components\NaiveUI\Pagination;
use Thinkrix\Schema\Components\Common\TableColumnSetting;
use Thinkrix\Schema\Actions\SetAction;
use Thinkrix\Schema\Actions\CallAction;
use Thinkrix\Schema\Actions\FetchAction;
use Thinkrix\Schema\Actions\IfAction;

/**
 * CrudPage - 简化版 CRUD 页面组件
 */
class CrudPage
{
    protected string $title = '';
    protected string $apiPrefix = '';
    protected array $apiParams = [];
    protected string $rowKey = 'id';
    protected array $columns = [];
    protected array $tableSlots = [];
    protected int $scrollX = 1000;
    protected bool $paginated = true;
    protected int $defaultPageSize = 15;
    protected array $pageSizes = [10, 20, 50, 100];
    protected bool $showSizePicker = true;
    protected bool $showQuickJumper = false;
    protected bool $showItemCount = false;
    protected bool $hideOnSinglePage = true;
    protected bool $isTree = false;
    protected string $childrenKey = 'children';
    protected bool $defaultExpandAll = false;
    protected ?string $expandedRowKeys = null;
    protected ?int $indent = null;
    protected bool $flexHeight = true;
    protected bool $virtualScroll = false;
    protected int $minRowHeight = 48;
    protected array $searchItems = [];
    protected array $toolbarLeft = [];
    protected array $toolbarRight = [];
    protected array $modals = [];
    protected array $drawers = [];
    protected array $extraData = [];
    protected array $extraMethods = [];

    public function __construct(string $title = '') { $this->title = $title; }
    public static function make(string $title = ''): static { return new static($title); }

    public function title(string $title): static { $this->title = $title; return $this; }
    public function apiPrefix(string $prefix): static { $this->apiPrefix = $prefix; return $this; }
    public function apiParams(array $params): static { $this->apiParams = $params; return $this; }
    public function rowKey(string $key): static { $this->rowKey = $key; return $this; }

    public function columns(array $columns): static
    {
        $this->columns = [];
        $this->tableSlots = [];
        foreach ($columns as $col) {
            if (isset($col['slot'])) {
                $this->tableSlots[$col['key']] = [
                    'content' => $col['slot'],
                    'slotProps' => $col['slotProps'] ?? 'slotData',
                ];
                unset($col['slot'], $col['slotProps']);
            }
            $this->columns[] = $col;
        }
        return $this;
    }

    public function scrollX(int $width): static { $this->scrollX = $width; return $this; }
    public function pagination(bool $enabled = true): static { $this->paginated = $enabled; return $this; }
    public function defaultPageSize(int $size): static { $this->defaultPageSize = $size; return $this; }
    public function pageSizes(array $sizes): static { $this->pageSizes = $sizes; return $this; }
    public function showSizePicker(bool $show = true): static { $this->showSizePicker = $show; return $this; }
    public function showQuickJumper(bool $show = true): static { $this->showQuickJumper = $show; return $this; }
    public function showItemCount(bool $show = true): static { $this->showItemCount = $show; return $this; }
    public function hideOnSinglePage(bool $hide = true): static { $this->hideOnSinglePage = $hide; return $this; }

    public function tree(string $childrenKey = 'children', bool $defaultExpandAll = false, ?int $indent = null): static
    {
        $this->isTree = true;
        $this->childrenKey = $childrenKey;
        $this->defaultExpandAll = $defaultExpandAll;
        $this->indent = $indent;
        $this->paginated = false;
        return $this;
    }

    public function expandedRowKeys(string $expression): static
    {
        $this->expandedRowKeys = $expression;
        return $this;
    }

    public function flexHeight(bool $flexHeight = true): static
    {
        $this->flexHeight = $flexHeight;
        return $this;
    }

    public function virtualScroll(bool $enabled = true, int $minRowHeight = 48): static
    {
        $this->virtualScroll = $enabled;
        $this->minRowHeight = $minRowHeight;
        return $this;
    }

    public function search(array $items): static { $this->searchItems = $items; return $this; }
    public function toolbarLeft(array $items): static { $this->toolbarLeft = $items; return $this; }
    public function toolbarRight(array $items): static { $this->toolbarRight = $items; return $this; }

    public function modal(string $name, string $title, string|array|Component|OptForm $content, array $options = []): static
    {
        $this->modals[$name] = [
            'title' => $title,
            'content' => $content,
            'width' => $options['width'] ?? '500px',
            'data' => $options['data'] ?? [],
            'onClose' => $options['onClose'] ?? null,
        ];
        return $this;
    }

    public function drawer(string $name, string $title, string|array|Component|OptForm $content, array $options = []): static
    {
        $this->drawers[$name] = [
            'title' => $title,
            'content' => $content,
            'width' => $options['width'] ?? 500,
            'placement' => $options['placement'] ?? 'right',
            'data' => $options['data'] ?? [],
            'onClose' => $options['onClose'] ?? null,
        ];
        return $this;
    }

    public function data(array $data): static
    {
        $this->extraData = array_merge($this->extraData, $data);
        return $this;
    }

    public function method(string $name, array $actions): static
    {
        $this->extraMethods[$name] = $actions;
        return $this;
    }

    public function methods(array $methods): static
    {
        $this->extraMethods = array_merge($this->extraMethods, $methods);
        return $this;
    }

    public function build(): array
    {
        $schema = Card::make()
            ->props([
                'title' => $this->title,
                'style' => ['height' => '100%', 'display' => 'flex', 'flexDirection' => 'column'],
                'contentStyle' => ['flex' => '1 1 0%', 'overflow' => 'hidden', 'display' => 'flex', 'flexDirection' => 'column'],
            ])
            ->data($this->buildData())
            ->methods($this->buildMethods())
            ->onMounted(CallAction::make('loadData'))
            ->children($this->buildChildren());

        return $schema->toArray();
    }

    protected function buildData(): array
    {
        $data = [
            'searchForm' => $this->buildSearchFormData(),
            'tableData' => [],
            'loading' => false,
            'columns' => $this->buildColumnChecks(),
        ];

        if ($this->paginated) {
            $data['pagination'] = ['page' => 1, 'pageSize' => $this->defaultPageSize, 'total' => 0];
        }

        $allToolbarItems = array_merge($this->toolbarLeft, $this->toolbarRight);
        foreach ($allToolbarItems as $item) {
            if ($item === 'batchDelete') {
                $data['selectedRowKeys'] = [];
            }
        }

        if ($this->isTree) {
            $data['expandedRowKeys'] = [];
        }

        foreach ($this->modals as $name => $config) {
            $data["{$name}Visible"] = false;
            foreach ($config['data'] as $key => $value) {
                $data[$key] = $value;
            }
        }

        foreach ($this->drawers as $name => $config) {
            $data["{$name}Visible"] = false;
            foreach ($config['data'] as $key => $value) {
                $data[$key] = $value;
            }
        }

        return array_merge($data, $this->extraData);
    }

    protected function buildSearchFormData(): array
    {
        $data = [];
        foreach ($this->searchItems as $item) {
            $name = $item[1];
            if (array_key_exists(3, $item)) {
                $default = $item[3];
                if ($default === 'null') { $default = null; }
            } else {
                $default = '';
            }
            $data[$name] = $default;
        }
        return $data;
    }

    protected function buildColumnChecks(): array
    {
        return array_map(fn($col) => array_merge($col, [
            'title' => $col['title'] ?? $col['key'],
            'checked' => true,
            'visible' => true,
        ]), $this->columns);
    }

    protected function buildMethods(): array
    {
        $methods = [
            'loadData' => $this->buildLoadDataMethod(),
            'search' => $this->buildSearchMethod(),
            'resetSearch' => $this->buildResetSearchMethod(),
        ];

        if ($this->paginated) {
            $methods['handlePageChange'] = [
                SetAction::make('pagination.page', '{{ $event }}'),
                CallAction::make('loadData'),
            ];
            $methods['handlePageSizeChange'] = [
                SetAction::make('pagination.pageSize', '{{ $event }}'),
                SetAction::make('pagination.page', 1),
                CallAction::make('loadData'),
            ];
        }

        $allToolbarItems = array_merge($this->toolbarLeft, $this->toolbarRight);
        foreach ($allToolbarItems as $item) {
            if ($item === 'batchDelete' && !isset($methods['handleSelectionChange'])) {
                $methods['handleSelectionChange'] = [SetAction::make('selectedRowKeys', '{{ $event }}')];
                $methods['handleBatchDelete'] = [
                    FetchAction::make($this->apiPrefix)->delete()
                        ->body(['action_type' => 'batch', 'ids' => '{{ selectedRowKeys }}'])
                        ->then([CallAction::make('$message.success', ['批量删除成功']), SetAction::make('selectedRowKeys', []), CallAction::make('loadData')])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "批量删除失败" }}'])]),
                ];
            }
            if ($item === 'exportCurrent' && !isset($methods['handleExportCurrent'])) {
                $methods['handleExportCurrent'] = [
                    FetchAction::make($this->apiPrefix)
                        ->params(['action_type' => 'export', 'type' => 'current', 'page' => '{{ pagination.page }}', 'page_size' => '{{ pagination.pageSize }}'])
                        ->responseType('blob')
                        ->then([CallAction::make('$methods.$download', ['{{ $response }}', '导出数据.xlsx'])])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "导出失败" }}'])]),
                ];
            }
            if ($item === 'exportAll' && !isset($methods['handleExportAll'])) {
                $methods['handleExportAll'] = [
                    FetchAction::make($this->apiPrefix)
                        ->params(['action_type' => 'export', 'type' => 'all'])->responseType('blob')
                        ->then([CallAction::make('$methods.$download', ['{{ $response }}', '导出数据.xlsx'])])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "导出失败" }}'])]),
                ];
            }
            if ($item === 'print' && !isset($methods['handlePrint'])) {
                $methods['handlePrint'] = [CallAction::make('$methods.$window.print')];
            }
            if ($item === 'expandAll' && !isset($methods['handleExpandAll'])) {
                $methods['handleExpandAll'] = [['script' => "const getAllKeys = (items) => items.reduce((keys, item) => { keys.push(item.{$this->rowKey}); if (item.{$this->childrenKey}) keys.push(...getAllKeys(item.{$this->childrenKey})); return keys; }, []); state.expandedRowKeys = getAllKeys(state.tableData);"]];
            }
            if ($item === 'collapseAll' && !isset($methods['handleCollapseAll'])) {
                $methods['handleCollapseAll'] = [SetAction::make('expandedRowKeys', [])];
            }
        }

        foreach ($this->modals as $name => $config) {
            $closeMethod = "handle" . ucfirst($name) . "Close";
            $methods[$closeMethod] = $config['onClose'] ?? [SetAction::make("{$name}Visible", false)];
        }

        foreach ($this->drawers as $name => $config) {
            $closeMethod = "handle" . ucfirst($name) . "Close";
            $methods[$closeMethod] = $config['onClose'] ?? [SetAction::make("{$name}Visible", false)];
        }

        return array_merge($methods, $this->extraMethods);
    }

    protected function buildLoadDataMethod(): array
    {
        $params = [];
        foreach ($this->apiParams as $key => $value) { $params[$key] = $value; }
        foreach ($this->searchItems as $item) {
            $params[$item[1]] = "{{ searchForm.{$item[1]} }}";
        }
        if ($this->paginated) {
            $params['page'] = '{{ pagination.page }}';
            $params['page_size'] = '{{ pagination.pageSize }}';
        }

        if ($this->paginated) {
            $thenActions = [
                IfAction::make('!$response.data || typeof $response.data !== "object"')
                    ->then([CallAction::make('$message.error', ['数据格式错误：分页模式下接口应返回 { list: [], total: number } 格式']), CallAction::make('console.error', ['期望格式: { data: { list: [], total: number } }, 实际返回:', '{{ $response }}'])])
                    ->else([
                        IfAction::make('!Array.isArray($response.data.list)')
                            ->then([CallAction::make('$message.error', ['数据格式错误：data.list 应为数组']), CallAction::make('console.error', ['期望 data.list 为数组, 实际返回:', '{{ $response.data }}']), SetAction::make('tableData', [])])
                            ->else([SetAction::make('tableData', '{{ $response.data.list }}')]),
                        IfAction::make('typeof $response.data.total !== "number"')
                            ->then([CallAction::make('$message.warning', ['数据格式警告：data.total 应为数字，已自动转换']), CallAction::make('console.warn', ['期望 data.total 为数字, 实际返回:', '{{ $response.data.total }}']), SetAction::make('pagination.total', '{{ parseInt($response.data.total) || 0 }}')])
                            ->else([SetAction::make('pagination.total', '{{ $response.data.total }}')]),
                    ]),
            ];
        } else {
            $thenActions = [
                IfAction::make('!Array.isArray($response.data)')
                    ->then([CallAction::make('$message.error', ['数据格式错误：非分页模式下接口应直接返回数组']), CallAction::make('console.error', ['期望格式: { data: [] }, 实际返回:', '{{ $response }}']), SetAction::make('tableData', [])])
                    ->else([SetAction::make('tableData', '{{ $response.data }}')]),
            ];
        }

        return [
            SetAction::make('loading', true),
            FetchAction::make($this->apiPrefix)->params($params)
                ->then($thenActions)
                ->catch([CallAction::make('$message.error', ['{{ $error.message || "加载数据失败" }}'])])
                ->finally([SetAction::make('loading', false)]),
        ];
    }

    protected function buildSearchMethod(): array
    {
        $actions = [];
        if ($this->paginated) { $actions[] = SetAction::make('pagination.page', 1); }
        $actions[] = CallAction::make('loadData');
        return $actions;
    }

    protected function buildResetSearchMethod(): array
    {
        $actions = [];
        foreach ($this->searchItems as $item) {
            $name = $item[1];
            $default = $item[3] ?? '';
            $actions[] = SetAction::make("searchForm.{$name}", $default);
        }
        $actions[] = CallAction::make('search');
        return $actions;
    }

    protected function buildChildren(): array
    {
        $spaceChildren = [];
        if (!empty($this->searchItems)) { $spaceChildren[] = $this->buildSearchForm(); }
        $toolbar = $this->buildToolbar();
        if ($toolbar) { $spaceChildren[] = $toolbar; }
        $spaceChildren[] = $this->buildDataTable();
        if ($this->paginated) { $spaceChildren[] = $this->buildPagination(); }

        $children = [
            Space::make()->props(['vertical' => true, 'size' => 'large', 'wrapItem' => false,
                'style' => ['height' => '100%', 'display' => 'flex', 'flexDirection' => 'column'],
            ])->children($spaceChildren),
        ];

        foreach ($this->modals as $name => $config) { $children[] = $this->buildModal($name, $config); }
        foreach ($this->drawers as $name => $config) { $children[] = $this->buildDrawer($name, $config); }

        return $children;
    }

    protected function buildSearchForm(): Component
    {
        $formItems = [];
        foreach ($this->searchItems as $item) {
            [$label, $name, $component] = $item;
            $component->model("searchForm.{$name}");
            $formItems[] = FormItem::make()->label($label)->children([$component]);
        }
        $formItems[] = FormItem::make()->children([
            Space::make()->children([
                Button::make()->type('primary')->on('click', ['call' => 'search'])->text('搜索'),
                Button::make()->on('click', ['call' => 'resetSearch'])->text('重置'),
            ]),
        ]);
        return Form::make()->inline()->props(['labelPlacement' => 'left'])->children($formItems);
    }

    protected function buildToolbar(): ?Component
    {
        $leftComponents = $this->buildToolbarItems($this->toolbarLeft);
        $rightComponents = $this->buildToolbarItems($this->toolbarRight);
        if (empty($leftComponents) && empty($rightComponents)) { return null; }
        if (empty($rightComponents)) { return Space::make()->children($leftComponents); }
        if (empty($leftComponents)) { return Space::make()->props(['justify' => 'end'])->children($rightComponents); }
        return Space::make()->props(['justify' => 'space-between', 'style' => ['width' => '100%']])
            ->children([Space::make()->children($leftComponents), Space::make()->children($rightComponents)]);
    }

    protected function buildToolbarItems(array $items): array
    {
        $components = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $component = $this->buildBuiltinToolbarItem($item);
                if ($component) { $components[] = $component; }
            } elseif ($item instanceof Component) {
                $components[] = $item;
            }
        }
        return $components;
    }

    protected function buildBuiltinToolbarItem(string $type): ?Component
    {
        return match ($type) {
            'columnSelector' => TableColumnSetting::make()->columns('columns'),
            'batchDelete' => Button::make()->type('error')->props(['disabled' => '{{ selectedRowKeys.length === 0 }}'])->on('click', ['call' => 'handleBatchDelete'])->text('批量删除'),
            'exportCurrent' => Button::make()->on('click', ['call' => 'handleExportCurrent'])->text('导出当前页'),
            'exportAll' => Button::make()->on('click', ['call' => 'handleExportAll'])->text('导出全部'),
            'print' => Button::make()->on('click', ['call' => 'handlePrint'])->text('打印'),
            'expandAll' => Button::make()->on('click', ['call' => 'handleExpandAll'])->text('展开全部'),
            'collapseAll' => Button::make()->on('click', ['call' => 'handleCollapseAll'])->text('折叠全部'),
            default => null,
        };
    }

    protected function buildDataTable(): Component
    {
        $tableProps = [
            'loading' => '{{ loading }}', 'data' => '{{ tableData }}',
            'columns' => '{{ columns.filter(c => c.checked) }}',
            'rowKey' => "{{ row => row.{$this->rowKey} }}",
            'scrollX' => $this->scrollX, 'flexHeight' => $this->flexHeight,
        ];

        if ($this->virtualScroll) { $tableProps['virtualScroll'] = true; $tableProps['minRowHeight'] = $this->minRowHeight; }
        if ($this->flexHeight) { $tableProps['style'] = ['flex' => '1 1 0%', 'overflow' => 'hidden']; }
        if ($this->isTree) {
            $tableProps['childrenKey'] = $this->childrenKey;
            $tableProps['defaultExpandAll'] = $this->defaultExpandAll;
            if ($this->indent !== null) { $tableProps['indent'] = $this->indent; }
            $tableProps['expandedRowKeys'] = $this->expandedRowKeys ?: '{{ expandedRowKeys }}';
        }

        $hasBatchDelete = in_array('batchDelete', [...$this->toolbarLeft, ...$this->toolbarRight]);
        if ($hasBatchDelete) { $tableProps['checkedRowKeys'] = '{{ selectedRowKeys }}'; }

        $table = DataTable::make()->props($tableProps);
        if ($hasBatchDelete) { $table->on('update:checked-row-keys', ['call' => 'handleSelectionChange', 'args' => ['{{ $event }}']]); }
        if ($this->isTree) { $table->on('update:expanded-row-keys', [SetAction::make('expandedRowKeys', '{{ $event }}')]); }

        foreach ($this->tableSlots as $column => $config) {
            $table->slot($column, $config['content'], $config['slotProps']);
        }

        return $table;
    }

    protected function buildPagination(): Component
    {
        $flex = Flex::make()->props(['justify' => 'end', 'class' => 'mt-4']);
        if ($this->hideOnSinglePage) { $flex->if('pagination.total > pagination.pageSize'); }

        return $flex->children([
            Pagination::make()->props([
                'page' => '{{ pagination.page }}', 'pageSize' => '{{ pagination.pageSize }}',
                'itemCount' => '{{ pagination.total }}',
                'pageSizes' => $this->pageSizes,
                'showSizePicker' => $this->showSizePicker,
                'showQuickJumper' => $this->showQuickJumper,
                'showItemCount' => $this->showItemCount,
            ])->on('update:page', ['call' => 'handlePageChange'])->on('update:pageSize', ['call' => 'handlePageSizeChange']),
        ]);
    }

    protected function buildModal(string $name, array $config): Component
    {
        $content = $config['content'];
        if ($content instanceof OptForm) { $modalChildren = [$content->toArray()]; }
        elseif ($content instanceof Component) { $modalChildren = [$content]; }
        elseif (is_string($content)) { $modalChildren = [$content]; }
        else { $modalChildren = $content; }

        return Modal::make()->props(['show' => "{{ {$name}Visible }}", 'title' => $config['title'],
            'style' => ['width' => $config['width']], 'preset' => 'card',
        ])->on('update:show', ['call' => "handle" . ucfirst($name) . "Close"])
            ->children($modalChildren);
    }

    protected function buildDrawer(string $name, array $config): Component
    {
        $content = $config['content'];
        if ($content instanceof OptForm) { $drawerChildren = [$content->toArray()]; }
        elseif ($content instanceof Component) { $drawerChildren = [$content]; }
        elseif (is_string($content)) { $drawerChildren = [$content]; }
        else { $drawerChildren = $content; }

        return Drawer::make()->props(['show' => "{{ {$name}Visible }}", 'width' => $config['width'], 'placement' => $config['placement']])
            ->on('update:show', ['call' => "handle" . ucfirst($name) . "Close"])
            ->children([DrawerContent::make()->props(['title' => $config['title']])->children($drawerChildren)]);
    }
}

<?php

namespace Thinkrix\Controllers;

use think\Request;
use Thinkrix\Models\DictGroup;
use Thinkrix\Models\DictItem;
use Thinkrix\Services\DataDictService;
use Thinkrix\Schema\Components\NaiveUI\Input;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Space;
use Thinkrix\Schema\Components\NaiveUI\Tag;
use Thinkrix\Schema\Components\NaiveUI\Popconfirm;
use Thinkrix\Schema\Components\NaiveUI\SwitchC;
use Thinkrix\Schema\Components\Business\CrudPage;
use Thinkrix\Schema\Components\Business\DataTable;
use Thinkrix\Schema\Components\Business\OptForm;
use Thinkrix\Schema\Components\NaiveUI\Modal;
use Thinkrix\Schema\Actions\SetAction;
use Thinkrix\Schema\Actions\CallAction;
use Thinkrix\Schema\Actions\FetchAction;
use Thinkrix\Schema\Actions\IfAction;

class DictController extends Controller
{
    protected DataDictService $dictService;

    public function __construct(DataDictService $dictService)
    {
        $this->dictService = $dictService;
    }

    public function groups(): array
    {
        $actionType = $this->input('action_type', 'list');
        return match ($actionType) {
            'list_ui' => $this->groupsListUi(),
            default => $this->groupsList(),
        };
    }

    protected function groupsList(): array
    {
        $query = DictGroup::withCount('items');
        if ($keyword = $this->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")->whereOr('name', 'like', "%{$keyword}%");
            });
        }
        $groups = $query->order('id', 'desc')->paginate($this->input('page_size', 20));
        return success(['list' => $groups->items(), 'total' => $groups->total()]);
    }

    public function items(int $groupId): array
    {
        $group = DictGroup::findOrFail($groupId);
        $items = $group->items()->order('sort')->order('id')->select();
        return success(['group' => $group, 'list' => $items->toArray()]);
    }

    public function createGroup(): array
    {
        $data = request()->post();
        $this->validate($data, ['code' => 'require|max:50|unique:dict_groups', 'name' => 'require|max:100']);
        return success('创建成功', DictGroup::create($data)->toArray());
    }

    public function showGroup(int $id): array { return success(DictGroup::findOrFail($id)->toArray()); }

    public function updateGroup(int $id): array
    {
        $group = DictGroup::findOrFail($id);
        $data = request()->put();
        $this->validate($data, ['name' => 'require|max:100']);
        $group->save($data);
        $this->dictService->clearCache($group->code);
        return success('更新成功', $group->toArray());
    }

    public function deleteGroup(int $id): array
    {
        $group = DictGroup::findOrFail($id);
        if ($group->is_system) { return error('系统内置分组不允许删除'); }
        $this->dictService->clearCache($group->code);
        $group->delete();
        return success('删除成功');
    }

    public function createItem(int $groupId): array
    {
        DictGroup::findOrFail($groupId);
        $data = request()->post();
        $this->validate($data, ['code' => 'require|max:50', 'label' => 'require|max:100', 'value' => 'require|max:100']);
        $data['group_id'] = $groupId;
        $data['sort'] = $data['sort'] ?? 0;
        $data['is_enabled'] = $data['is_enabled'] ?? true;
        return success('创建成功', DictItem::create($data)->toArray());
    }

    public function showItem(int $groupId, int $id): array
    {
        DictGroup::findOrFail($groupId);
        return success(DictItem::where('group_id', $groupId)->findOrFail($id)->toArray());
    }

    public function updateItem(int $groupId, int $id): array
    {
        $group = DictGroup::findOrFail($groupId);
        $item = DictItem::where('group_id', $groupId)->findOrFail($id);
        $item->save(request()->put());
        $this->dictService->clearCache($group->code);
        return success('更新成功', $item->toArray());
    }

    public function deleteItem(int $groupId, int $id): array
    {
        $group = DictGroup::findOrFail($groupId);
        DictItem::where('group_id', $groupId)->findOrFail($id)->delete();
        $this->dictService->clearCache($group->code);
        return success('删除成功');
    }

    public function sortItems(int $groupId): array
    {
        DictGroup::findOrFail($groupId);
        $data = request()->post();
        $this->validate($data, ['items' => 'require|array']);
        foreach ($data['items'] as $item) {
            DictItem::where('id', $item['id'])->where('group_id', $groupId)->update(['sort' => $item['sort']]);
        }
        return success('排序更新成功');
    }

    public function options(string $code): array { return success($this->dictService->selectOptions($code)); }

    public function batchOptions(): array
    {
        $data = request()->post();
        $this->validate($data, ['codes' => 'require|array']);
        $result = [];
        foreach ($data['codes'] as $code) { $result[$code] = $this->dictService->selectOptions($code); }
        return success($result);
    }

    protected function groupsListUi(): array
    {
        $groupForm = OptForm::make('formData')
            ->fields([
                ['编码', 'code', Input::make()->props(['placeholder' => '请输入编码，如 order_status', 'disabled' => '{{ !!editingId && editingSystem }}'])],
                ['名称', 'name', Input::make()->props(['placeholder' => '请输入名称'])],
                ['描述', 'description', Input::make()->props(['type' => 'textarea', 'placeholder' => '请输入描述'])],
            ])->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        $itemForm = OptForm::make('itemFormData')
            ->fields([
                ['编码', 'code', Input::make()->props(['placeholder' => '请输入编码'])],
                ['显示文本', 'label', Input::make()->props(['placeholder' => '请输入显示文本'])],
                ['存储值', 'value', Input::make()->props(['placeholder' => '请输入存储值'])],
                ['排序', 'sort', Input::make()->props(['type' => 'number', 'placeholder' => '数字越小越靠前']), 0],
                ['启用状态', 'is_enabled', SwitchC::make(), true],
            ])->buttons([
                Button::make()->on('click', SetAction::make('itemFormVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ itemSubmitting }}'])->on('click', ['call' => 'handleItemSubmit'])->text('确定'),
            ]);

        $schema = CrudPage::make('字典管理')->apiPrefix('/dicts/groups')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'code', 'title' => '编码', 'width' => 150],
                ['key' => 'name', 'title' => '名称', 'width' => 150],
                ['key' => 'description', 'title' => '描述'],
                ['key' => 'items_count', 'title' => '字典项数', 'width' => 100, 'slot' => [Tag::make()->props(['type' => 'info', 'size' => 'small'])->children(['{{ slotData.row.items_count }}'])]],
                ['key' => 'is_system', 'title' => '系统内置', 'width' => 100, 'slot' => [Tag::make()->props(['type' => "{{ slotData.row.is_system ? 'warning' : 'default' }}", 'size' => 'small'])->children(["{{ slotData.row.is_system ? '是' : '否' }}"])]],
                ['key' => 'created_at', 'title' => '创建时间', 'width' => 180],
                ['key' => 'actions', 'title' => '操作', 'width' => 200, 'fixed' => 'right', 'slot' => [
                    Space::make()->children([
                        Button::make()->size('small')->props(['type' => 'primary', 'text' => true])->on('click', [SetAction::make('currentGroupId', '{{ slotData.row.id }}'), SetAction::make('currentGroupName', '{{ slotData.row.name }}'), SetAction::make('itemsVisible', true), CallAction::make('loadItems')])->text('字典项'),
                        Button::make()->size('small')->props(['type' => 'info', 'text' => true])->on('click', [SetAction::make('editingId', '{{ slotData.row.id }}'), SetAction::make('editingSystem', '{{ slotData.row.is_system }}'), SetAction::make('formData.code', '{{ slotData.row.code }}'), SetAction::make('formData.name', '{{ slotData.row.name }}'), SetAction::make('formData.description', '{{ slotData.row.description || "" }}'), SetAction::make('formVisible', true)])->text('编辑'),
                        Popconfirm::make()->if('!slotData.row.is_system')->props(['positiveText' => '确定', 'negativeText' => '取消'])
                            ->on('positive-click', FetchAction::make('/dicts/groups/{{ slotData.row.id }}')->delete()->then([CallAction::make('$message.success', ['删除成功']), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                            ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text('删除')])->children(['确定要删除该字典分组吗？']),
                    ]),
                ]],
            ])
            ->scrollX(1100)->search([['关键词', 'keyword', Input::make()->props(['placeholder' => '搜索编码/名称', 'clearable' => true])]])
            ->toolbarLeft([Button::make()->type('primary')->on('click', [SetAction::batch(['editingId' => null, 'editingSystem' => false, 'formData.code' => '', 'formData.name' => '', 'formData.description' => '', 'formVisible' => true])])->text('新增分组')])
            ->data(['formData' => $groupForm->getDefaultData(), 'editingId' => null, 'editingSystem' => false, 'submitting' => false, 'currentGroupId' => null, 'currentGroupName' => '', 'itemsData' => [], 'itemsLoading' => false, 'itemFormData' => $itemForm->getDefaultData(), 'editingItemId' => null, 'itemSubmitting' => false, 'itemFormVisible' => false])
            ->methods([
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    IfAction::make('editingId')
                        ->then(FetchAction::make('{{ "/dicts/groups/" + editingId }}')->put()->body('{{ formData }}')->then([CallAction::make('$message.success', ['更新成功']), SetAction::make('formVisible', false), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)]))
                        ->else(FetchAction::make('/dicts/groups')->post()->body('{{ formData }}')->then([CallAction::make('$message.success', ['创建成功']), SetAction::make('formVisible', false), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)])),
                ],
                'loadItems' => [SetAction::make('itemsLoading', true), FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items" }}')->then([SetAction::make('itemsData', '{{ $response.data.list || [] }}')])->catch([CallAction::make('$message.error', ['{{ $error.message || "加载字典项失败" }}'])])->finally([SetAction::make('itemsLoading', false)])],
                'handleItemSubmit' => [
                    SetAction::make('itemSubmitting', true),
                    IfAction::make('editingItemId')
                        ->then(FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items/" + editingItemId }}')->put()->body('{{ itemFormData }}')->then([CallAction::make('$message.success', ['更新成功']), SetAction::make('itemFormVisible', false), CallAction::make('loadItems')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('itemSubmitting', false)]))
                        ->else(FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items" }}')->post()->body('{{ itemFormData }}')->then([CallAction::make('$message.success', ['创建成功']), SetAction::make('itemFormVisible', false), CallAction::make('loadItems')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('itemSubmitting', false)])),
                ],
            ])
            ->modal('form', '{{ editingId ? "编辑字典分组" : "新增字典分组" }}', $groupForm)
            ->drawer('items', '{{ currentGroupName + " - 字典项管理" }}', $this->buildItemsDrawerContent($itemForm), ['width' => 800]);

        return success($schema->build());
    }

    protected function buildItemsDrawerContent(OptForm $itemForm): array
    {
        $itemsTable = DataTable::make()->props([
            'loading' => '{{ itemsLoading }}', 'data' => '{{ itemsData }}',
            'columns' => [
                ['key' => 'sort', 'title' => '排序', 'width' => 60],
                ['key' => 'code', 'title' => '编码', 'width' => 120],
                ['key' => 'label', 'title' => '显示文本', 'width' => 120],
                ['key' => 'value', 'title' => '存储值', 'width' => 100],
                ['key' => 'is_enabled', 'title' => '状态', 'width' => 80],
                ['key' => 'actions', 'title' => '操作', 'width' => 120, 'fixed' => 'right'],
            ], 'rowKey' => '{{ row => row.id }}', 'scrollX' => 700,
        ])->slot('is_enabled', [Tag::make()->props(['type' => "{{ slotData.row.is_enabled ? 'success' : 'default' }}", 'size' => 'small'])->children(["{{ slotData.row.is_enabled ? '启用' : '禁用' }}"])], 'slotData')
            ->slot('actions', [Space::make()->children([
                Button::make()->size('small')->props(['type' => 'info', 'text' => true])->on('click', [SetAction::make('editingItemId', '{{ slotData.row.id }}'), SetAction::make('itemFormData.code', '{{ slotData.row.code }}'), SetAction::make('itemFormData.label', '{{ slotData.row.label }}'), SetAction::make('itemFormData.value', '{{ slotData.row.value }}'), SetAction::make('itemFormData.sort', '{{ slotData.row.sort }}'), SetAction::make('itemFormData.is_enabled', '{{ slotData.row.is_enabled }}'), SetAction::make('itemFormVisible', true)])->text('编辑'),
                Popconfirm::make()->props(['positiveText' => '确定', 'negativeText' => '取消'])
                    ->on('positive-click', FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items/" + slotData.row.id }}')->delete()->then([CallAction::make('$message.success', ['删除成功']), CallAction::make('loadItems')])->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                    ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text('删除')])->children(['确定要删除该字典项吗？']),
            ])], 'slotData');

        return [
            Space::make()->props(['vertical' => true, 'size' => 'large', 'wrapItem' => false])->children([
                Button::make()->type('primary')->size('small')->on('click', [SetAction::batch(['editingItemId' => null, 'itemFormData.code' => '', 'itemFormData.label' => '', 'itemFormData.value' => '', 'itemFormData.sort' => 0, 'itemFormData.is_enabled' => true, 'itemFormVisible' => true])])->text('新增字典项'),
                $itemsTable,
            ]),
            Modal::make()->props(['show' => '{{ itemFormVisible }}', 'title' => '{{ editingItemId ? "编辑字典项" : "新增字典项" }}', 'style' => ['width' => '500px'], 'preset' => 'card'])->on('update:show', [SetAction::make('itemFormVisible', false)])->children([$itemForm->toArray()]),
        ];
    }
}

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
        return success(__t('crud.created'), DictGroup::create($data)->toArray());
    }

    public function showGroup(int $id): array { return success(DictGroup::findOrFail($id)->toArray()); }

    public function updateGroup(int $id): array
    {
        $group = DictGroup::findOrFail($id);
        $data = request()->put();
        $this->validate($data, ['name' => 'require|max:100']);
        $group->save($data);
        $this->dictService->clearCache($group->code);
        return success(__t('crud.updated'), $group->toArray());
    }

    public function deleteGroup(int $id): array
    {
        $group = DictGroup::findOrFail($id);
        if ($group->is_system) { return error(__t('system.builtin_not_deletable')); }
        $this->dictService->clearCache($group->code);
        $group->delete();
        return success(__t('crud.deleted'));
    }

    public function createItem(int $groupId): array
    {
        DictGroup::findOrFail($groupId);
        $data = request()->post();
        $this->validate($data, ['code' => 'require|max:50', 'label' => 'require|max:100', 'value' => 'require|max:100']);
        $data['group_id'] = $groupId;
        $data['sort'] = $data['sort'] ?? 0;
        $data['is_enabled'] = $data['is_enabled'] ?? true;
        return success(__t('crud.created'), DictItem::create($data)->toArray());
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
        return success(__t('crud.updated'), $item->toArray());
    }

    public function deleteItem(int $groupId, int $id): array
    {
        $group = DictGroup::findOrFail($groupId);
        DictItem::where('group_id', $groupId)->findOrFail($id)->delete();
        $this->dictService->clearCache($group->code);
        return success(__t('crud.deleted'));
    }

    public function sortItems(int $groupId): array
    {
        DictGroup::findOrFail($groupId);
        $data = request()->post();
        $this->validate($data, ['items' => 'require|array']);
        foreach ($data['items'] as $item) {
            DictItem::where('id', $item['id'])->where('group_id', $groupId)->update(['sort' => $item['sort']]);
        }
        return success(__t('crud.order_updated'));
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
                [__t('dict.column.code'), 'code', Input::make()->props(['placeholder' => __t('dict.placeholder.code'), 'disabled' => '{{ !!editingId && editingSystem }}'])],
                [__t('dict.column.name'), 'name', Input::make()->props(['placeholder' => __t('dict.placeholder.name')])],
                [__t('module.column.description'), 'description', Input::make()->props(['type' => 'textarea', 'placeholder' => __t('dict.placeholder.description')])],
            ])->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text(__t('ui.button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text(__t('ui.button.confirm')),
            ]);

        $itemForm = OptForm::make('itemFormData')
            ->fields([
                [__t('dict.column.code'), 'code', Input::make()->props(['placeholder' => __t('dict.placeholder.item_code')])],
                [__t('dict.column.label'), 'label', Input::make()->props(['placeholder' => __t('dict.placeholder.item_label')])],
                [__t('dict.column.value'), 'value', Input::make()->props(['placeholder' => __t('dict.placeholder.item_value')])],
                [__t('permission.column.sort'), 'sort', Input::make()->props(['type' => 'number', 'placeholder' => __t('dict.placeholder.sort')]), 0],
                [__t('dict.column.enabled'), 'is_enabled', SwitchC::make(), true],
            ])->buttons([
                Button::make()->on('click', SetAction::make('itemFormVisible', false))->text(__t('ui.button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ itemSubmitting }}'])->on('click', ['call' => 'handleItemSubmit'])->text(__t('ui.button.confirm')),
            ]);

        $schema = CrudPage::make(__t('dict.title'))->apiPrefix('/dicts/groups')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'code', 'title' => __t('dict.column.code'), 'width' => 150],
                ['key' => 'name', 'title' => __t('dict.column.name'), 'width' => 150],
                ['key' => 'description', 'title' => __t('module.column.description')],
                ['key' => 'items_count', 'title' => __t('dict.column.items_count'), 'width' => 100, 'slot' => [Tag::make()->props(['type' => 'info', 'size' => 'small'])->children(['{{ slotData.row.items_count }}'])]],
                ['key' => 'is_system', 'title' => __t('dict.column.is_system'), 'width' => 100, 'slot' => [Tag::make()->props(['type' => "{{ slotData.row.is_system ? 'warning' : 'default' }}", 'size' => 'small'])->children(["{{ slotData.row.is_system ? __t('ui.button.yes') : __t('ui.button.no') }}"])]],
                ['key' => 'created_at', 'title' => __t('system.column.created_at'), 'width' => 180],
                ['key' => 'actions', 'title' => __t('module.column.actions'), 'width' => 200, 'fixed' => 'right', 'slot' => [
                    Space::make()->children([
                        Button::make()->size('small')->props(['type' => 'primary', 'text' => true])->on('click', [SetAction::make('currentGroupId', '{{ slotData.row.id }}'), SetAction::make('currentGroupName', '{{ slotData.row.name }}'), SetAction::make('itemsVisible', true), CallAction::make('loadItems')])->text(__t('dict.button.dict_items')),
                        Button::make()->size('small')->props(['type' => 'info', 'text' => true])->on('click', [SetAction::make('editingId', '{{ slotData.row.id }}'), SetAction::make('editingSystem', '{{ slotData.row.is_system }}'), SetAction::make('formData.code', '{{ slotData.row.code }}'), SetAction::make('formData.name', '{{ slotData.row.name }}'), SetAction::make('formData.description', '{{ slotData.row.description || "" }}'), SetAction::make('formVisible', true)])->text(__t('permission.button.edit')),
                        Popconfirm::make()->if('!slotData.row.is_system')->props(['positiveText' => __t('ui.button.confirm'), 'negativeText' => __t('ui.button.cancel')])
                            ->on('positive-click', FetchAction::make('/dicts/groups/{{ slotData.row.id }}')->delete()->then([CallAction::make('$message.success', [__t('crud.message.deleted')]), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                            ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text(__t('permission.button.delete'))])->children([__t('dict.confirm.delete_group')]),
                    ]),
                ]],
            ])
            ->scrollX(1100)->search([[__t('dict.search.keyword'), 'keyword', Input::make()->props(['placeholder' => __t('dict.search.placeholder'), 'clearable' => true])]])
            ->toolbarLeft([Button::make()->type('primary')->on('click', [SetAction::batch(['editingId' => null, 'editingSystem' => false, 'formData.code' => '', 'formData.name' => '', 'formData.description' => '', 'formVisible' => true])])->text(__t('dict.button.create_group'))])
            ->data(['formData' => $groupForm->getDefaultData(), 'editingId' => null, 'editingSystem' => false, 'submitting' => false, 'currentGroupId' => null, 'currentGroupName' => '', 'itemsData' => [], 'itemsLoading' => false, 'itemFormData' => $itemForm->getDefaultData(), 'editingItemId' => null, 'itemSubmitting' => false, 'itemFormVisible' => false])
            ->methods([
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    IfAction::make('editingId')
                        ->then(FetchAction::make('{{ "/dicts/groups/" + editingId }}')->put()->body('{{ formData }}')->then([CallAction::make('$message.success', [__t('crud.message.updated')]), SetAction::make('formVisible', false), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)]))
                        ->else(FetchAction::make('/dicts/groups')->post()->body('{{ formData }}')->then([CallAction::make('$message.success', [__t('crud.message.created')]), SetAction::make('formVisible', false), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)])),
                ],
                'loadItems' => [SetAction::make('itemsLoading', true), FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items" }}')->then([SetAction::make('itemsData', '{{ $response.data.list || [] }}')])->catch([CallAction::make('$message.error', ['{{ $error.message || "加载字典项失败" }}'])])->finally([SetAction::make('itemsLoading', false)])],
                'handleItemSubmit' => [
                    SetAction::make('itemSubmitting', true),
                    IfAction::make('editingItemId')
                        ->then(FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items/" + editingItemId }}')->put()->body('{{ itemFormData }}')->then([CallAction::make('$message.success', [__t('crud.message.updated')]), SetAction::make('itemFormVisible', false), CallAction::make('loadItems')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('itemSubmitting', false)]))
                        ->else(FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items" }}')->post()->body('{{ itemFormData }}')->then([CallAction::make('$message.success', [__t('crud.message.created')]), SetAction::make('itemFormVisible', false), CallAction::make('loadItems')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('itemSubmitting', false)])),
                ],
            ])
            ->modal('form', '{{ editingId ? "' . __t('dict.title.edit_group') . '" : "' . __t('dict.title.create_group') . '" }}', $groupForm)
            ->drawer('items', '{{ currentGroupName + " - " }}' . __t('dict.title.items'), $this->buildItemsDrawerContent($itemForm), ['width' => 800]);

        return success($schema->build());
    }

    protected function buildItemsDrawerContent(OptForm $itemForm): array
    {
        $itemsTable = DataTable::make()->props([
            'loading' => '{{ itemsLoading }}', 'data' => '{{ itemsData }}',
            'columns' => [
                ['key' => 'sort', 'title' => __t('permission.column.sort'), 'width' => 60],
                ['key' => 'code', 'title' => __t('dict.column.code'), 'width' => 120],
                ['key' => 'label', 'title' => __t('dict.column.label'), 'width' => 120],
                ['key' => 'value', 'title' => __t('dict.column.value'), 'width' => 100],
                ['key' => 'is_enabled', 'title' => __t('module.column.status'), 'width' => 80],
                ['key' => 'actions', 'title' => __t('module.column.actions'), 'width' => 120, 'fixed' => 'right'],
            ], 'rowKey' => '{{ row => row.id }}', 'scrollX' => 700,
        ])->slot('is_enabled', [Tag::make()->props(['type' => "{{ slotData.row.is_enabled ? 'success' : 'default' }}", 'size' => 'small'])->children(["{{ slotData.row.is_enabled ? __t('ui.tag.enabled') : __t('ui.tag.disabled') }}"])], 'slotData')
            ->slot('actions', [Space::make()->children([
                Button::make()->size('small')->props(['type' => 'info', 'text' => true])->on('click', [SetAction::make('editingItemId', '{{ slotData.row.id }}'), SetAction::make('itemFormData.code', '{{ slotData.row.code }}'), SetAction::make('itemFormData.label', '{{ slotData.row.label }}'), SetAction::make('itemFormData.value', '{{ slotData.row.value }}'), SetAction::make('itemFormData.sort', '{{ slotData.row.sort }}'), SetAction::make('itemFormData.is_enabled', '{{ slotData.row.is_enabled }}'), SetAction::make('itemFormVisible', true)])->text(__t('permission.button.edit')),
                Popconfirm::make()->props(['positiveText' => __t('ui.button.confirm'), 'negativeText' => __t('ui.button.cancel')])
                    ->on('positive-click', FetchAction::make('{{ "/dicts/groups/" + currentGroupId + "/items/" + slotData.row.id }}')->delete()->then([CallAction::make('$message.success', [__t('crud.message.deleted')]), CallAction::make('loadItems')])->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                    ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text(__t('permission.button.delete'))])->children([__t('dict.confirm.delete_item')]),
            ])], 'slotData');

        return [
            Space::make()->props(['vertical' => true, 'size' => 'large', 'wrapItem' => false])->children([
                Button::make()->type('primary')->size('small')->on('click', [SetAction::batch(['editingItemId' => null, 'itemFormData.code' => '', 'itemFormData.label' => '', 'itemFormData.value' => '', 'itemFormData.sort' => 0, 'itemFormData.is_enabled' => true, 'itemFormVisible' => true])])->text(__t('dict.title.new_item')),
                $itemsTable,
            ]),
            Modal::make()->props(['show' => '{{ itemFormVisible }}', 'title' => '{{ editingItemId ? "' . __t('dict.title.edit_item') . '" : "' . __t('dict.title.new_item') . '" }}', 'style' => ['width' => '500px'], 'preset' => 'card'])->on('update:show', [SetAction::make('itemFormVisible', false)])->children([$itemForm->toArray()]),
        ];
    }
}

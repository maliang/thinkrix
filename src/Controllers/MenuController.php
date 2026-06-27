<?php

namespace Thinkrix\Controllers;

use think\Request;
use think\Db;
use Thinkrix\Schema\Components\NaiveUI\Input;
use Thinkrix\Schema\Components\NaiveUI\InputNumber;
use Thinkrix\Schema\Components\NaiveUI\Select;
use Thinkrix\Schema\Components\NaiveUI\SwitchC;
use Thinkrix\Schema\Components\NaiveUI\TreeSelect;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Space;
use Thinkrix\Schema\Components\NaiveUI\Popconfirm;
use Thinkrix\Schema\Components\NaiveUI\Tag;
use Thinkrix\Schema\Components\Business\CrudPage;
use Thinkrix\Schema\Components\Business\OptForm;
use Thinkrix\Schema\Actions\SetAction;
use Thinkrix\Schema\Actions\CallAction;
use Thinkrix\Schema\Actions\FetchAction;
use Thinkrix\Schema\Actions\IfAction;

class MenuController extends CrudController
{
    protected function getModelClass(): string
    {
        return config('thinkrix.models.menu', \Thinkrix\Models\Menu::class);
    }
    protected function getResourceName(): string { return __t('menu.resource_name'); }
    protected function getTable(): string { return config('thinkrix.tables.menus', 'admin_menus'); }
    protected function getDefaultOrder(): array { return ['order', 'asc']; }

    protected function applyResourceScope($query): void
    {
        $query->where('guard_name', config('thinkrix.guard', 'admin'));
    }

    public function index(): mixed
    {
        $actionType = $this->input('action_type', 'list');
        return match ($actionType) {
            'all' => $this->all(),
            'list_ui' => $this->listUi(),
            'form_ui' => $this->listUi(),
            default => $this->list(),
        };
    }

    protected function list(): array
    {
        $modelClass = $this->getModelClass();
        $guard = config('thinkrix.guard', 'admin');
        $routes = $modelClass::getRoutesForUser($this->getUser(), $guard);
        return success($routes);
    }

    protected function prepareStoreData(array $validated): array
    {
        $validated['guard_name'] = config('thinkrix.guard', 'admin');
        if (($validated['badge'] ?? null) === []) {
            $validated['badge'] = null;
        }
        return $validated;
    }

    protected function prepareUpdateData(array $validated): array
    {
        if (($validated['badge'] ?? null) === []) {
            $validated['badge'] = null;
        }
        return $validated;
    }

    protected function getStoreRules(): array
    {
        $table = $this->getTable();
        return [
            'parent_id' => 'integer',
            'name' => "require|max:255|unique:{$table}",
            'path' => 'require|max:255',
            'component' => 'max:255',
            'redirect' => 'max:255',
            'title' => 'max:255',
            'icon' => 'max:255',
            'order' => 'integer',
            'permissions' => 'array',
            'badge' => 'array',
            'schema_source' => 'max:255',
            'href' => 'max:255',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        $table = $this->getTable();
        return [
            'parent_id' => 'integer',
            'name' => "require|max:255|unique:{$table},name,{$id}",
            'path' => 'require|max:255',
            'component' => 'max:255',
            'redirect' => 'max:255',
            'title' => 'max:255',
            'icon' => 'max:255',
            'order' => 'integer',
            'permissions' => 'array',
            'badge' => 'array',
            'schema_source' => 'max:255',
            'href' => 'max:255',
        ];
    }

    protected function validateStore(): array
    {
        $data = $this->normalizeBadgeInput(request()->post());
        return $this->validate($data, $this->getStoreRules());
    }

    protected function validateUpdate(int $id): array
    {
        $data = $this->normalizeBadgeInput(request()->put());
        $validated = $this->validate($data, $this->getUpdateRules($id));
        $this->assertValidParent($id, $validated['parent_id'] ?? null);
        return $validated;
    }

    protected function normalizeBadgeInput(array $data): array
    {
        if (!array_key_exists('badge', $data)) {
            return $data;
        }

        if ($data['badge'] === null || $data['badge'] === '') {
            $data['badge'] = [];
            return $data;
        }

        if (is_string($data['badge'])) {
            $decoded = json_decode($data['badge'], true);
            if (!is_array($decoded)) {
                throw new \Thinkrix\Exceptions\ApiException(__t('menu.message.badge_invalid'), 40022);
            }
            $data['badge'] = $decoded;
        }

        return $data;
    }

    protected function assertValidParent(int $id, mixed $parentId): void
    {
        $modelClass = $this->getModelClass();
        while ($parentId !== null && $parentId !== '') {
            if ((int) $parentId === $id) {
                throw new \Thinkrix\Exceptions\ApiException(__t('menu.message.cannot_parent_self'), 40022);
            }
            $parent = $modelClass::where('id', (int) $parentId)
                ->where('guard_name', config('thinkrix.guard', 'admin'))
                ->find();
            if (!$parent) {
                throw new \Thinkrix\Exceptions\ApiException(__t('menu.message.parent_not_found'), 40004);
            }
            $parentId = $parent?->parent_id;
        }
    }

    protected function beforeDelete($model): void
    {
        $children = $model->children()->select();
        if (!$children->isEmpty()) {
            throw new \Thinkrix\Exceptions\ApiException(__t('menu.message.delete_children_first'), 40022);
        }
    }

    protected function all(): array
    {
        $modelClass = $this->getModelClass();
        $guard = config('thinkrix.guard', 'admin');
        $menus = $modelClass::whereNull('parent_id')->forGuard($guard)->with('allChildren')->order('order')->select();
        $result = $this->transformMenuChildren($menus->toArray());
        return success($result);
    }

    protected function transformMenuChildren(array $menus): array
    {
        return array_map(function ($menu) {
            $children = $menu['allChildren'] ?? $menu['all_children'] ?? null;
            if (is_array($children)) {
                $menu['children'] = $this->transformMenuChildren($children);
            }
            unset($menu['allChildren'], $menu['all_children']);
            return $menu;
        }, $menus);
    }

    protected function updateSort(): array
    {
        $data = request()->put();
        $this->validate($data, ['items' => 'require|array']);
        $modelClass = $this->getModelClass();
        foreach ($data['items'] as $item) {
            $this->assertValidParent((int) $item['id'], $item['parent_id'] ?? null);
            $updated = $modelClass::where('id', $item['id'])
                ->where('guard_name', config('thinkrix.guard', 'admin'))
                ->update(['order' => $item['order'], 'parent_id' => $item['parent_id'] ?? null]);
            if (!$updated) {
                throw new \Thinkrix\Exceptions\ApiException(__t('menu.message.not_found'), 40004);
            }
        }
        return success(__t('crud.sorted'));
    }

    protected function listUi(): array
    {
        $yesLabel = $this->jsString(__t('ui.button.yes'));
        $noLabel = $this->jsString(__t('ui.button.no'));

        $menuForm = OptForm::make('formData')
            ->fields([
                [__t('menu.form.parent_id'), 'parent_id', TreeSelect::make()->props(['placeholder' => __t('menu.placeholder.parent'), 'clearable' => true, 'options' => '{{ menuTreeOptions }}', 'keyField' => 'id', 'labelField' => 'title', 'childrenField' => 'children'])],
                [__t('menu.column.name'), 'name', Input::make()->props(['placeholder' => __t('menu.placeholder.name')])],
                [__t('menu.column.title'), 'title', Input::make()->props(['placeholder' => __t('menu.placeholder.title')])],
                [__t('menu.column.path'), 'path', Input::make()->props(['placeholder' => __t('menu.placeholder.path')])],
                [__t('menu.column.icon'), 'icon', Input::make()->props(['placeholder' => __t('menu.placeholder.icon')])],
                [__t('menu.form.redirect'), 'redirect', Input::make()->props(['placeholder' => __t('menu.placeholder.redirect')])],
                [__t('permission.column.sort'), 'order', InputNumber::make()->props(['min' => 0]), 0],
                [__t('menu.form.layout_type'), 'layout_type', Select::make()->props(['clearable' => true, 'options' => [['label' => __t('menu.option.layout_normal'), 'value' => 'normal'], ['label' => __t('menu.option.layout_blank'), 'value' => 'blank']]])],
                [__t('menu.form.open_type'), 'open_type', Select::make()->props(['clearable' => true, 'options' => [['label' => __t('menu.option.open_normal'), 'value' => 'normal'], ['label' => __t('menu.option.open_iframe'), 'value' => 'iframe'], ['label' => __t('menu.option.open_new_window'), 'value' => 'newWindow']]])],
                [__t('menu.placeholder.href'), 'href', Input::make()->props(['placeholder' => __t('menu.placeholder.href')]), '', "formData.open_type === 'iframe' || formData.open_type === 'newWindow'"],
                [__t('menu.form.use_json_renderer'), 'use_json_renderer', SwitchC::make(), false],
                [__t('menu.column.schema_source'), 'schema_source', Input::make()->props(['placeholder' => __t('menu.placeholder.schema_source')]), '', 'formData.use_json_renderer'],
                [__t('menu.column.hide_in_menu'), 'hide_in_menu', SwitchC::make(), false],
                [__t('menu.form.keep_alive'), 'keep_alive', SwitchC::make(), false],
                [__t('menu.form.requires_auth'), 'requires_auth', SwitchC::make(), true],
                [__t('menu.form.is_default_after_login'), 'is_default_after_login', SwitchC::make(), false],
                [__t('menu.form.badge'), 'badge', Input::make()->props(['type' => 'textarea', 'placeholder' => __t('menu.placeholder.badge'), 'autosize' => ['minRows' => 3, 'maxRows' => 6]]), ''],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text(__t('ui.button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text(__t('ui.button.confirm')),
            ]);

        $schema = CrudPage::make(__t('menu.title'))->apiPrefix('/menus')->apiParams(['action_type' => 'all'])
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'title', 'title' => __t('menu.column.title')],
                ['key' => 'name', 'title' => __t('menu.column.name')],
                ['key' => 'path', 'title' => __t('menu.column.path')],
                ['key' => 'icon', 'title' => __t('menu.column.icon')],
                ['key' => 'order', 'title' => __t('permission.column.sort'), 'width' => 80],
                ['key' => 'hide_in_menu', 'title' => __t('menu.column.hide_in_menu'), 'width' => 80, 'slot' => [Tag::make()->props(['type' => "{{ slotData.row.hide_in_menu ? 'warning' : 'success' }}", 'size' => 'small'])->children(["{{ slotData.row.hide_in_menu ? {$yesLabel} : {$noLabel} }}"])]],
                ['key' => 'actions', 'title' => __t('module.column.actions'), 'width' => 200, 'fixed' => 'right', 'slot' => [
                    Space::make()->children([
                        Button::make()->size('small')->props(['type' => 'primary', 'text' => true])->on('click', [SetAction::make('editingId', '{{ slotData.row.id }}'), SetAction::make('formData.parent_id', '{{ slotData.row.parent_id }}'), SetAction::make('formData.name', '{{ slotData.row.name }}'), SetAction::make('formData.title', '{{ slotData.row.title || "" }}'), SetAction::make('formData.path', '{{ slotData.row.path }}'), SetAction::make('formData.icon', '{{ slotData.row.icon || "" }}'), SetAction::make('formData.redirect', '{{ slotData.row.redirect || "" }}'), SetAction::make('formData.order', '{{ slotData.row.order || 0 }}'), SetAction::make('formData.layout_type', '{{ slotData.row.layout_type }}'), SetAction::make('formData.open_type', '{{ slotData.row.open_type }}'), SetAction::make('formData.href', '{{ slotData.row.href || "" }}'), SetAction::make('formData.use_json_renderer', '{{ slotData.row.use_json_renderer || false }}'), SetAction::make('formData.schema_source', '{{ slotData.row.schema_source || "" }}'), SetAction::make('formData.hide_in_menu', '{{ slotData.row.hide_in_menu || false }}'), SetAction::make('formData.keep_alive', '{{ slotData.row.keep_alive || false }}'), SetAction::make('formData.requires_auth', '{{ slotData.row.requires_auth !== false }}'), SetAction::make('formData.is_default_after_login', '{{ slotData.row.is_default_after_login || false }}'), SetAction::make('formData.badge', '{{ slotData.row.badge ? JSON.stringify(slotData.row.badge, null, 2) : "" }}'), SetAction::make('formVisible', true), CallAction::make('loadMenuTree')])->text(__t('permission.button.edit')),
                        Button::make()->size('small')->props(['type' => 'success', 'text' => true])->on('click', ['call' => 'handleAddChild', 'args' => ['{{ slotData.row }}']])->text(__t('menu.button.add_child')),
                        Popconfirm::make()->props(['positiveText' => __t('ui.button.confirm'), 'negativeText' => __t('ui.button.cancel')])
                            ->on('positive-click', FetchAction::make('/menus/{{ slotData.row.id }}')->delete()->then([CallAction::make('$message.success', [__t('crud.message.deleted')]), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                            ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text(__t('permission.button.delete'))])->children([__t('menu.confirm.delete')]),
                    ]),
                ]],
            ])
            ->scrollX(1200)->pagination(false)->tree()
            ->toolbarLeft([Button::make()->type('primary')->on('click', [SetAction::batch(['editingId' => null, 'formData' => $menuForm->getDefaultData(), 'formVisible' => true]), CallAction::make('loadMenuTree')])->text(__t('permission.button.create')), 'expandAll', 'collapseAll'])
            ->data(['formData' => $menuForm->getDefaultData(), 'editingId' => null, 'submitting' => false, 'menuTreeOptions' => []])
            ->methods([
                'loadMenuTree' => [FetchAction::make('/menus?action_type=all')->get()->then([SetAction::make('menuTreeOptions', '{{ $response.data || [] }}')])],
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    IfAction::make('editingId')
                        ->then(FetchAction::make('{{ "/menus/" + editingId }}')->put()->body('{{ formData }}')->then([CallAction::make('$message.success', [__t('crud.message.updated')]), SetAction::make('formVisible', false), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)]))
                        ->else(FetchAction::make('/menus')->post()->body('{{ formData }}')->then([CallAction::make('$message.success', [__t('crud.message.created')]), SetAction::make('formVisible', false), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)])),
                ],
                'handleAddChild' => [SetAction::batch(['editingId' => null, 'formData' => array_merge($menuForm->getDefaultData(), ['parent_id' => '{{ $event.id }}']), 'formVisible' => true]), CallAction::make('loadMenuTree')],
            ])
            ->modal('form', '{{ editingId ? "' . __t('menu.title.edit') . '" : "' . __t('menu.title.create') . '" }}', $menuForm, ['width' => '600px']);

        return success($schema->build());
    }
}

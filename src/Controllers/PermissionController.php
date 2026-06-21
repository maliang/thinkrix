<?php

namespace Thinkrix\Controllers;

use think\Request;
use Thinkrix\Services\PermissionService;
use Thinkrix\Schema\Components\NaiveUI\Input;
use Thinkrix\Schema\Components\NaiveUI\InputNumber;
use Thinkrix\Schema\Components\NaiveUI\TreeSelect;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Space;
use Thinkrix\Schema\Components\NaiveUI\Popconfirm;
use Thinkrix\Schema\Components\Business\CrudPage;
use Thinkrix\Schema\Components\Business\OptForm;
use Thinkrix\Schema\Actions\SetAction;
use Thinkrix\Schema\Actions\CallAction;
use Thinkrix\Schema\Actions\FetchAction;
use Thinkrix\Schema\Actions\IfAction;

class PermissionController extends CrudController
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    protected function getModelClass(): string
    {
        return config('thinkrix.models.permission', \Thinkrix\Models\Permission::class);
    }
    protected function getResourceName(): string { return __t('role.column.permissions'); }
    protected function getTable(): string { return config('thinkrix.tables.permissions', 'permissions'); }
    protected function getDefaultOrder(): array { return ['sort', 'asc']; }

    protected function applyResourceScope($query): void
    {
        $query->where('guard_name', config('thinkrix.guard', 'admin'));
    }

    public function index(): mixed
    {
        $actionType = $this->input('action_type', 'list');
        return match ($actionType) {
            'tree' => $this->tree(),
            'all' => $this->all(),
            'list_ui' => $this->listUi(),
            'form_ui' => $this->listUi(),
            default => $this->list(),
        };
    }

    protected function applySearch($query): void
    {
        if ($keyword = $this->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")->whereOr('title', 'like', "%{$keyword}%");
            });
        }
    }

    protected function applyFilters($query): void
    {
        if ($module = $this->input('module')) {
            $query->where('module', $module);
        }
    }

    protected function prepareStoreData(array $validated): array
    {
        return [
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'guard_name' => config('thinkrix.guard', 'admin'),
            'module' => $validated['module'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort' => $validated['sort'] ?? 0,
        ];
    }

    protected function getStoreRules(): array
    {
        $table = $this->getTable();
        return [
            'parent_id' => 'integer',
            'name' => "require|max:255|unique:{$table}",
            'title' => 'max:255',
            'module' => 'max:255',
            'description' => 'max:1000',
            'sort' => 'integer',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        $table = $this->getTable();
        return [
            'parent_id' => 'integer',
            'name' => "require|max:255|unique:{$table},name,{$id}",
            'title' => 'max:255',
            'module' => 'max:255',
            'description' => 'max:1000',
            'sort' => 'integer',
        ];
    }

    protected function validateUpdate(int $id): array
    {
        $validated = parent::validateUpdate($id);
        $this->assertValidParent($id, $validated['parent_id'] ?? null);
        return $validated;
    }

    protected function assertValidParent(int $id, mixed $parentId): void
    {
        $modelClass = $this->getModelClass();
        while ($parentId !== null && $parentId !== '') {
            if ((int) $parentId === $id) {
                throw new \Thinkrix\Exceptions\ApiException(__t('permission.message.cannot_parent_self'), 40022);
            }
            $parent = $modelClass::where('id', (int) $parentId)
                ->where('guard_name', config('thinkrix.guard', 'admin'))
                ->find();
            if (!$parent) {
                throw new \Thinkrix\Exceptions\ApiException(__t('permission.message.parent_not_found'), 40004);
            }
            $parentId = $parent?->parent_id;
        }
    }

    protected function beforeDelete($model): void
    {
        $children = $model->children()->select();
        if (!$children->isEmpty()) {
            throw new \Thinkrix\Exceptions\ApiException(__t('permission.message.delete_children_first'), 40022);
        }
    }

    protected function tree(): array
    {
        $modelClass = $this->getModelClass();
        return success($modelClass::getTreeByModule(config('thinkrix.guard', 'admin')));
    }

    protected function all(): array
    {
        $modelClass = $this->getModelClass();
        $permissions = $modelClass::whereNull('parent_id')
            ->where('guard_name', config('thinkrix.guard', 'admin'))
            ->with('allChildren')->order('sort')->select();
        $result = $this->transformPermissionChildren($permissions->toArray());
        return success($result);
    }

    protected function transformPermissionChildren(array $permissions): array
    {
        return array_map(function ($p) {
            $children = $p['allChildren'] ?? $p['all_children'] ?? null;
            if (is_array($children)) {
                $p['children'] = $this->transformPermissionChildren($children);
            }
            unset($p['allChildren'], $p['all_children']);
            return $p;
        }, $permissions);
    }

    protected function listUi(): array
    {
        $permForm = OptForm::make('formData')
            ->fields([
                [__t('permission.form.parent_id'), 'parent_id', TreeSelect::make()->props(['placeholder' => __t('permission.placeholder.parent'), 'clearable' => true, 'options' => '{{ permissionTreeOptions }}', 'keyField' => 'id', 'labelField' => 'title', 'childrenField' => 'children'])],
                [__t('permission.column.name'), 'name', Input::make()->props(['placeholder' => __t('permission.placeholder.name')])],
                [__t('permission.column.title'), 'title', Input::make()->props(['placeholder' => __t('permission.placeholder.title')])],
                [__t('permission.column.module'), 'module', Input::make()->props(['placeholder' => __t('permission.placeholder.module')])],
                [__t('module.column.description'), 'description', Input::make()->props(['type' => 'textarea', 'placeholder' => __t('permission.placeholder.desc')])],
                [__t('permission.column.sort'), 'sort', InputNumber::make()->props(['min' => 0]), 0],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text(__t('ui.button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text(__t('ui.button.confirm')),
            ]);

        $schema = CrudPage::make(__t('permission.title'))->apiPrefix('/permissions')->apiParams(['action_type' => 'all'])
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 120],
                ['key' => 'name', 'title' => __t('permission.column.name')],
                ['key' => 'title', 'title' => __t('permission.column.title')],
                ['key' => 'module', 'title' => __t('permission.column.module')],
                ['key' => 'description', 'title' => __t('module.column.description')],
                ['key' => 'sort', 'title' => __t('permission.column.sort'), 'width' => 80],
                ['key' => 'actions', 'title' => __t('module.column.actions'), 'width' => 200, 'fixed' => 'right', 'slot' => [
                    Space::make()->children([
                        Button::make()->size('small')->props(['type' => 'primary', 'text' => true])->on('click', [SetAction::make('editingId', '{{ slotData.row.id }}'), SetAction::make('formData.parent_id', '{{ slotData.row.parent_id }}'), SetAction::make('formData.name', '{{ slotData.row.name }}'), SetAction::make('formData.title', '{{ slotData.row.title || "" }}'), SetAction::make('formData.module', '{{ slotData.row.module || "" }}'), SetAction::make('formData.description', '{{ slotData.row.description || "" }}'), SetAction::make('formData.sort', '{{ slotData.row.sort || 0 }}'), SetAction::make('formVisible', true), CallAction::make('loadPermissionTree')])->text(__t('permission.button.edit')),
                        Button::make()->size('small')->props(['type' => 'success', 'text' => true])->on('click', ['call' => 'handleAddChild', 'args' => ['{{ slotData.row }}']])->text(__t('permission.button.add_child')),
                        Popconfirm::make()->props(['positiveText' => __t('ui.button.confirm'), 'negativeText' => __t('ui.button.cancel')])
                            ->on('positive-click', FetchAction::make('/permissions/{{ slotData.row.id }}')->delete()->then([CallAction::make('$message.success', [__t('crud.message.deleted')]), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                            ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text(__t('permission.button.delete'))])->children([__t('permission.confirm.delete')]),
                    ]),
                ]],
            ])
            ->scrollX(1000)->pagination(false)->tree()
            ->toolbarLeft([Button::make()->type('primary')->on('click', [SetAction::batch(['editingId' => null, 'formData.parent_id' => null, 'formData.name' => '', 'formData.title' => '', 'formData.module' => '', 'formData.description' => '', 'formData.sort' => 0, 'formVisible' => true]), CallAction::make('loadPermissionTree')])->text(__t('permission.button.create')), 'expandAll', 'collapseAll'])
            ->data(['formData' => $permForm->getDefaultData(), 'editingId' => null, 'submitting' => false, 'permissionTreeOptions' => []])
            ->methods([
                'loadPermissionTree' => [FetchAction::make('/permissions?action_type=all')->get()->then([SetAction::make('permissionTreeOptions', '{{ $response.data || [] }}')])],
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    IfAction::make('editingId')
                        ->then(FetchAction::make('{{ "/permissions/" + editingId }}')->put()->body('{{ formData }}')->then([CallAction::make('$message.success', [__t('crud.message.updated')]), SetAction::make('formVisible', false), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)]))
                        ->else(FetchAction::make('/permissions')->post()->body('{{ formData }}')->then([CallAction::make('$message.success', [__t('crud.message.created')]), SetAction::make('formVisible', false), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)])),
                ],
                'handleAddChild' => [SetAction::batch(['editingId' => null, 'formData.parent_id' => '{{ $event.id }}', 'formData.name' => '', 'formData.title' => '', 'formData.module' => '{{ $event.module || "" }}', 'formData.description' => '', 'formData.sort' => 0, 'formVisible' => true]), CallAction::make('loadPermissionTree')],
            ])
            ->modal('form', '{{ editingId ? "' . __t('permission.title.edit') . '" : "' . __t('permission.title.create') . '" }}', $permForm, ['width' => '500px']);

        return success($schema->build());
    }
}

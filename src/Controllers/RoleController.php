<?php

namespace Thinkrix\Controllers;

use think\Request;
use think\Db;
use Thinkrix\Services\PermissionService;
use Thinkrix\Schema\Components\NaiveUI\Input;
use Thinkrix\Schema\Components\NaiveUI\Select;
use Thinkrix\Schema\Components\NaiveUI\SwitchC;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Space;
use Thinkrix\Schema\Components\NaiveUI\Tag;
use Thinkrix\Schema\Components\NaiveUI\Popconfirm;
use Thinkrix\Schema\Components\NaiveUI\Tree;
use Thinkrix\Schema\Components\Business\CrudPage;
use Thinkrix\Schema\Components\Business\OptForm;
use Thinkrix\Schema\Actions\SetAction;
use Thinkrix\Schema\Actions\CallAction;
use Thinkrix\Schema\Actions\FetchAction;
use Thinkrix\Schema\Actions\IfAction;

class RoleController extends CrudController
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    protected function getModelClass(): string
    {
        return config('thinkrix.models.role', \Thinkrix\Models\Role::class);
    }
    protected function getResourceName(): string { return __t('user.column.roles'); }
    protected function getTable(): string { return config('thinkrix.tables.roles', 'roles'); }
    protected function getDefaultOrder(): array { return ['id', 'asc']; }
    protected function getListWith(): array { return ['permissions']; }

    protected function applyResourceScope($query): void
    {
        $query->where('guard_name', config('thinkrix.guard', 'admin'));
    }

    protected function list(): array
    {
        $query = $this->buildListQuery();
        $data = $query->select();
        return success($data->toArray());
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
        if ($this->input('status') !== null && $this->input('status') !== '') {
            $query->where('status', (int) $this->input('status'));
        }
    }

    protected function prepareStoreData(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'title' => $validated['title'] ?? null,
            'guard_name' => config('thinkrix.guard', 'admin'),
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? true,
            'is_system' => false,
        ];
    }

    protected function getStoreRules(): array
    {
        $table = $this->getTable();
        return [
            'name' => "require|max:255|unique:{$table}",
            'title' => 'max:255',
            'description' => 'max:1000',
            'status' => 'boolean',
            'permissions' => 'array',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        $table = $this->getTable();
        return [
            'name' => "require|max:255|unique:{$table},name,{$id}",
            'title' => 'max:255',
            'description' => 'max:1000',
            'status' => 'boolean',
            'permissions' => 'array',
        ];
    }

    protected function afterStore($model, array $validated): void
    {
        if (!empty($validated['permissions'])) {
            $this->permissionService->syncRolePermissions($model, $validated['permissions']);
        }
    }

    protected function afterUpdate($model, array $validated): void
    {
        if (isset($validated['permissions'])) {
            $this->permissionService->syncRolePermissions($model, $validated['permissions']);
        }
    }

    protected function beforeDelete($model): void
    {
        if ($model->isSystemRole()) {
            throw new \Thinkrix\Exceptions\ApiException(__t('role.message.cannot_delete_system'), 40100);
        }
    }

    protected function updatePermissions(int $id): array
    {
        $model = $this->findOrFail($id);
        $data = request()->put();
        $this->validate($data, ['permissions' => 'require|array']);
        $this->permissionService->syncRolePermissions($model, $data['permissions']);
        return success(__t('permission.updated'));
    }

    protected function listUi(): array
    {
        $permissionTree = Tree::make()->props([
            'data' => $this->getPermissionTree(), 'checkable' => true, 'selectable' => false,
            'cascade' => true, 'keyField' => 'name', 'labelField' => 'title', 'childrenField' => 'children',
            'virtualScroll' => true, 'style' => ['maxHeight' => '300px'],
        ])->model(['checkedKeys' => 'formData.permissions']);

        $roleForm = OptForm::make('formData')
            ->fields([
                [__t('role.column.name'), 'name', Input::make()->props(['placeholder' => __t('role.placeholder.name'), 'disabled' => '{{ !!editingId }}'])],
                [__t('role.column.title'), 'title', Input::make()->props(['placeholder' => __t('role.placeholder.title')])],
                [__t('module.column.description'), 'description', Input::make()->props(['type' => 'textarea', 'placeholder' => __t('role.placeholder.description')])],
                [__t('role.column.permissions'), 'permissions', $permissionTree, []],
                [__t('module.column.status'), 'status', SwitchC::make(), true],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text(__t('ui.button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text(__t('ui.button.confirm')),
            ]);

        $schema = CrudPage::make(__t('role.title'))->apiPrefix('/roles')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'name', 'title' => __t('role.column.name')],
                ['key' => 'title', 'title' => __t('role.column.title')],
                ['key' => 'description', 'title' => __t('module.column.description')],
                ['key' => 'status', 'title' => __t('module.column.status'), 'width' => 80, 'slot' => [Tag::make()->props(['type' => "{{ slotData.row.status ? 'success' : 'error' }}", 'size' => 'small'])->children(["{{ slotData.row.status ? __t('ui.tag.enabled') : __t('ui.tag.disabled') }}"])]],
                ['key' => 'is_system', 'title' => __t('role.column.is_system'), 'width' => 100, 'slot' => [Tag::make()->props(['type' => "{{ slotData.row.is_system ? 'warning' : 'default' }}", 'size' => 'small'])->children(["{{ slotData.row.is_system ? __t('ui.button.yes') : __t('ui.button.no') }}"])]],
                ['key' => 'actions', 'title' => __t('module.column.actions'), 'width' => 150, 'fixed' => 'right', 'slot' => [
                    Space::make()->children([
                        Button::make()->size('small')->props(['type' => 'primary', 'text' => true])->on('click', [SetAction::make('editingId', '{{ slotData.row.id }}'), SetAction::make('formData.name', '{{ slotData.row.name }}'), SetAction::make('formData.title', '{{ slotData.row.title || "" }}'), SetAction::make('formData.description', '{{ slotData.row.description || "" }}'), SetAction::make('formData.permissions', '{{ (slotData.row.permissions || []).map(p => p.name) }}'), SetAction::make('formData.status', '{{ slotData.row.status }}'), SetAction::make('formVisible', true)])->text(__t('permission.button.edit')),
                        Popconfirm::make()->if('!slotData.row.is_system')->props(['positiveText' => __t('ui.button.confirm'), 'negativeText' => __t('ui.button.cancel')])
                            ->on('positive-click', FetchAction::make('/roles/{{ slotData.row.id }}')->delete()->then([CallAction::make('$message.success', [__t('crud.message.deleted')]), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                            ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text(__t('permission.button.delete'))])
                            ->children([__t('role.confirm.delete')]),
                    ]),
                ]],
            ])
            ->scrollX(1000)->pagination(false)
            ->search([[__t('dict.search.keyword'), 'keyword', Input::make()->props(['placeholder' => __t('role.search.placeholder'), 'clearable' => true])]])
            ->toolbarLeft([Button::make()->type('primary')->on('click', [SetAction::batch(['editingId' => null, 'formData.name' => '', 'formData.title' => '', 'formData.description' => '', 'formData.permissions' => [], 'formData.status' => true, 'formVisible' => true])])->text(__t('permission.button.create'))])
            ->data(['formData' => $roleForm->getDefaultData(), 'editingId' => null, 'submitting' => false])
            ->methods(['handleSubmit' => [
                SetAction::make('submitting', true),
                IfAction::make('editingId')
                    ->then(FetchAction::make('{{ "/roles/" + editingId }}')->put()->body('{{ formData }}')
                        ->then([CallAction::make('$message.success', [__t('crud.message.updated')]), SetAction::make('formVisible', false), CallAction::make('loadData')])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)]))
                    ->else(FetchAction::make('/roles')->post()->body('{{ formData }}')
                        ->then([CallAction::make('$message.success', [__t('crud.message.created')]), SetAction::make('formVisible', false), CallAction::make('loadData')])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)])),
            ]])
            ->modal('form', '{{ editingId ? "' . __t('role.title.edit') . '" : "' . __t('role.title.create') . '" }}', $roleForm, ['width' => '600px']);

        return success($schema->build());
    }

    protected function getPermissionTree(): array
    {
        $permissionModel = config('thinkrix.models.permission', \Thinkrix\Models\Permission::class);
        $permissions = $permissionModel::whereNull('parent_id')
            ->where('guard_name', config('thinkrix.guard', 'admin'))
            ->with('allChildren')->order('sort')->select();
        $result = [];
        foreach ($permissions as $p) {
            $result[] = $this->formatPermissionNode($p);
        }
        return $result;
    }

    protected function formatPermissionNode($permission): array
    {
        $node = ['name' => $permission->name, 'title' => $permission->title ?: $permission->name];
        $allChildren = $permission->getRelation('allChildren') ?? $permission->getRelation('all_children');
        if ($allChildren && !$allChildren->isEmpty()) {
            $children = [];
            foreach ($allChildren as $child) {
                $children[] = $this->formatPermissionNode($child);
            }
            $node['children'] = $children;
        }
        return $node;
    }
}

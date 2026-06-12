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
    protected function getResourceName(): string { return '角色'; }
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
            throw new \Thinkrix\Exceptions\ApiException('不能删除系统内置角色', 40100);
        }
    }

    protected function updatePermissions(int $id): array
    {
        $model = $this->findOrFail($id);
        $data = request()->put();
        $this->validate($data, ['permissions' => 'require|array']);
        $this->permissionService->syncRolePermissions($model, $data['permissions']);
        return success('权限更新成功');
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
                ['角色标识', 'name', Input::make()->props(['placeholder' => '请输入角色标识（英文）', 'disabled' => '{{ !!editingId }}'])],
                ['角色名称', 'title', Input::make()->props(['placeholder' => '请输入角色名称'])],
                ['描述', 'description', Input::make()->props(['type' => 'textarea', 'placeholder' => '请输入角色描述'])],
                ['权限', 'permissions', $permissionTree, []],
                ['状态', 'status', SwitchC::make(), true],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        $schema = CrudPage::make('角色管理')->apiPrefix('/roles')
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'name', 'title' => '角色标识'],
                ['key' => 'title', 'title' => '角色名称'],
                ['key' => 'description', 'title' => '描述'],
                ['key' => 'status', 'title' => '状态', 'width' => 80, 'slot' => [Tag::make()->props(['type' => "{{ slotData.row.status ? 'success' : 'error' }}", 'size' => 'small'])->children(["{{ slotData.row.status ? '启用' : '禁用' }}"])]],
                ['key' => 'is_system', 'title' => '系统角色', 'width' => 100, 'slot' => [Tag::make()->props(['type' => "{{ slotData.row.is_system ? 'warning' : 'default' }}", 'size' => 'small'])->children(["{{ slotData.row.is_system ? '是' : '否' }}"])]],
                ['key' => 'actions', 'title' => '操作', 'width' => 150, 'fixed' => 'right', 'slot' => [
                    Space::make()->children([
                        Button::make()->size('small')->props(['type' => 'primary', 'text' => true])->on('click', [SetAction::make('editingId', '{{ slotData.row.id }}'), SetAction::make('formData.name', '{{ slotData.row.name }}'), SetAction::make('formData.title', '{{ slotData.row.title || "" }}'), SetAction::make('formData.description', '{{ slotData.row.description || "" }}'), SetAction::make('formData.permissions', '{{ (slotData.row.permissions || []).map(p => p.name) }}'), SetAction::make('formData.status', '{{ slotData.row.status }}'), SetAction::make('formVisible', true)])->text('编辑'),
                        Popconfirm::make()->if('!slotData.row.is_system')->props(['positiveText' => '确定', 'negativeText' => '取消'])
                            ->on('positive-click', FetchAction::make('/roles/{{ slotData.row.id }}')->delete()->then([CallAction::make('$message.success', ['删除成功']), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                            ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text('删除')])
                            ->children(['确定要删除该角色吗？']),
                    ]),
                ]],
            ])
            ->scrollX(1000)->pagination(false)
            ->search([['关键词', 'keyword', Input::make()->props(['placeholder' => '角色标识/名称', 'clearable' => true])]])
            ->toolbarLeft([Button::make()->type('primary')->on('click', [SetAction::batch(['editingId' => null, 'formData.name' => '', 'formData.title' => '', 'formData.description' => '', 'formData.permissions' => [], 'formData.status' => true, 'formVisible' => true])])->text('新增')])
            ->data(['formData' => $roleForm->getDefaultData(), 'editingId' => null, 'submitting' => false])
            ->methods(['handleSubmit' => [
                SetAction::make('submitting', true),
                IfAction::make('editingId')
                    ->then(FetchAction::make('{{ "/roles/" + editingId }}')->put()->body('{{ formData }}')
                        ->then([CallAction::make('$message.success', ['更新成功']), SetAction::make('formVisible', false), CallAction::make('loadData')])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)]))
                    ->else(FetchAction::make('/roles')->post()->body('{{ formData }}')
                        ->then([CallAction::make('$message.success', ['创建成功']), SetAction::make('formVisible', false), CallAction::make('loadData')])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)])),
            ]])
            ->modal('form', '{{ editingId ? "编辑角色" : "新增角色" }}', $roleForm, ['width' => '600px']);

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

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
    protected function getResourceName(): string { return '菜单'; }
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
            'schema_source' => 'max:255',
            'href' => 'max:255',
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
                throw new \Thinkrix\Exceptions\ApiException('不能将自己或自己的子菜单设为父级菜单', 40022);
            }
            $parent = $modelClass::where('id', (int) $parentId)
                ->where('guard_name', config('thinkrix.guard', 'admin'))
                ->find();
            if (!$parent) {
                throw new \Thinkrix\Exceptions\ApiException('父级菜单不存在', 40004);
            }
            $parentId = $parent?->parent_id;
        }
    }

    protected function beforeDelete($model): void
    {
        $children = $model->children()->select();
        if (!$children->isEmpty()) {
            throw new \Thinkrix\Exceptions\ApiException('请先删除子菜单', 40022);
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
                throw new \Thinkrix\Exceptions\ApiException('菜单不存在', 40004);
            }
        }
        return success('排序成功');
    }

    protected function listUi(): array
    {
        $menuForm = OptForm::make('formData')
            ->fields([
                ['父级菜单', 'parent_id', TreeSelect::make()->props(['placeholder' => '无（顶级菜单）', 'clearable' => true, 'options' => '{{ menuTreeOptions }}', 'keyField' => 'id', 'labelField' => 'title', 'childrenField' => 'children'])],
                ['菜单名称', 'name', Input::make()->props(['placeholder' => '路由名称（英文）'])],
                ['菜单标题', 'title', Input::make()->props(['placeholder' => '显示的菜单标题'])],
                ['路由路径', 'path', Input::make()->props(['placeholder' => '如：/user'])],
                ['图标', 'icon', Input::make()->props(['placeholder' => '如：mdi:account'])],
                ['重定向', 'redirect', Input::make()->props(['placeholder' => '重定向路径'])],
                ['排序', 'order', InputNumber::make()->props(['min' => 0]), 0],
                ['布局类型', 'layout_type', Select::make()->props(['clearable' => true, 'options' => [['label' => '普通布局', 'value' => 'normal'], ['label' => '空白布局', 'value' => 'blank']]])],
                ['打开方式', 'open_type', Select::make()->props(['clearable' => true, 'options' => [['label' => '正常打开', 'value' => 'normal'], ['label' => 'iframe 嵌入', 'value' => 'iframe'], ['label' => '新窗口打开', 'value' => 'newWindow']]])],
                ['外链地址', 'href', Input::make()->props(['placeholder' => '外部链接地址']), '', "formData.open_type === 'iframe' || formData.open_type === 'newWindow'"],
                ['使用 JSON 渲染', 'use_json_renderer', SwitchC::make(), false],
                ['Schema 来源', 'schema_source', Input::make()->props(['placeholder' => 'API 地址或静态文件路径']), '', 'formData.use_json_renderer'],
                ['隐藏菜单', 'hide_in_menu', SwitchC::make(), false],
                ['缓存页面', 'keep_alive', SwitchC::make(), false],
                ['需要认证', 'requires_auth', SwitchC::make(), true],
                ['登录后默认页', 'is_default_after_login', SwitchC::make(), false],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        $schema = CrudPage::make('菜单管理')->apiPrefix('/menus')->apiParams(['action_type' => 'all'])
            ->columns([
                ['key' => 'id', 'title' => 'ID', 'width' => 80],
                ['key' => 'title', 'title' => '菜单标题'],
                ['key' => 'name', 'title' => '路由名称'],
                ['key' => 'path', 'title' => '路由路径'],
                ['key' => 'icon', 'title' => '图标'],
                ['key' => 'order', 'title' => '排序', 'width' => 80],
                ['key' => 'hide_in_menu', 'title' => '隐藏', 'width' => 80, 'slot' => [Tag::make()->props(['type' => "{{ slotData.row.hide_in_menu ? 'warning' : 'success' }}", 'size' => 'small'])->children(["{{ slotData.row.hide_in_menu ? '是' : '否' }}"])]],
                ['key' => 'actions', 'title' => '操作', 'width' => 200, 'fixed' => 'right', 'slot' => [
                    Space::make()->children([
                        Button::make()->size('small')->props(['type' => 'primary', 'text' => true])->on('click', [SetAction::make('editingId', '{{ slotData.row.id }}'), SetAction::make('formData.parent_id', '{{ slotData.row.parent_id }}'), SetAction::make('formData.name', '{{ slotData.row.name }}'), SetAction::make('formData.title', '{{ slotData.row.title || "" }}'), SetAction::make('formData.path', '{{ slotData.row.path }}'), SetAction::make('formData.icon', '{{ slotData.row.icon || "" }}'), SetAction::make('formData.redirect', '{{ slotData.row.redirect || "" }}'), SetAction::make('formData.order', '{{ slotData.row.order || 0 }}'), SetAction::make('formData.layout_type', '{{ slotData.row.layout_type }}'), SetAction::make('formData.open_type', '{{ slotData.row.open_type }}'), SetAction::make('formData.href', '{{ slotData.row.href || "" }}'), SetAction::make('formData.use_json_renderer', '{{ slotData.row.use_json_renderer || false }}'), SetAction::make('formData.schema_source', '{{ slotData.row.schema_source || "" }}'), SetAction::make('formData.hide_in_menu', '{{ slotData.row.hide_in_menu || false }}'), SetAction::make('formData.keep_alive', '{{ slotData.row.keep_alive || false }}'), SetAction::make('formData.requires_auth', '{{ slotData.row.requires_auth !== false }}'), SetAction::make('formData.is_default_after_login', '{{ slotData.row.is_default_after_login || false }}'), SetAction::make('formVisible', true), CallAction::make('loadMenuTree')])->text('编辑'),
                        Button::make()->size('small')->props(['type' => 'success', 'text' => true])->on('click', ['call' => 'handleAddChild', 'args' => ['{{ slotData.row }}']])->text('添加子菜单'),
                        Popconfirm::make()->props(['positiveText' => '确定', 'negativeText' => '取消'])
                            ->on('positive-click', FetchAction::make('/menus/{{ slotData.row.id }}')->delete()->then([CallAction::make('$message.success', ['删除成功']), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                            ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text('删除')])->children(['确定要删除该菜单吗？']),
                    ]),
                ]],
            ])
            ->scrollX(1200)->pagination(false)->tree()
            ->toolbarLeft([Button::make()->type('primary')->on('click', [SetAction::batch(['editingId' => null, 'formData' => $menuForm->getDefaultData(), 'formVisible' => true]), CallAction::make('loadMenuTree')])->text('新增'), 'expandAll', 'collapseAll'])
            ->data(['formData' => $menuForm->getDefaultData(), 'editingId' => null, 'submitting' => false, 'menuTreeOptions' => []])
            ->methods([
                'loadMenuTree' => [FetchAction::make('/menus?action_type=all')->get()->then([SetAction::make('menuTreeOptions', '{{ $response.data || [] }}')])],
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    IfAction::make('editingId')
                        ->then(FetchAction::make('{{ "/menus/" + editingId }}')->put()->body('{{ formData }}')->then([CallAction::make('$message.success', ['更新成功']), SetAction::make('formVisible', false), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)]))
                        ->else(FetchAction::make('/menus')->post()->body('{{ formData }}')->then([CallAction::make('$message.success', ['创建成功']), SetAction::make('formVisible', false), CallAction::make('loadData')])->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)])),
                ],
                'handleAddChild' => [SetAction::batch(['editingId' => null, 'formData' => array_merge($menuForm->getDefaultData(), ['parent_id' => '{{ $event.id }}']), 'formVisible' => true]), CallAction::make('loadMenuTree')],
            ])
            ->modal('form', '{{ editingId ? "编辑菜单" : "新增菜单" }}', $menuForm, ['width' => '600px']);

        return success($schema->build());
    }
}

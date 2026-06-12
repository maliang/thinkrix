<?php

namespace Thinkrix\Controllers;

use think\Request;
use Thinkrix\Services\AuthService;
use Thinkrix\Schema\Components\NaiveUI\Input;
use Thinkrix\Schema\Components\NaiveUI\Select;
use Thinkrix\Schema\Components\NaiveUI\SwitchC;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Space;
use Thinkrix\Schema\Components\NaiveUI\Tag;
use Thinkrix\Schema\Components\NaiveUI\Popconfirm;
use Thinkrix\Schema\Components\Custom\Html;
use Thinkrix\Schema\Components\Business\CrudPage;
use Thinkrix\Schema\Components\Business\OptForm;
use Thinkrix\Schema\Actions\SetAction;
use Thinkrix\Schema\Actions\CallAction;
use Thinkrix\Schema\Actions\FetchAction;
use Thinkrix\Schema\Actions\IfAction;

class UserController extends CrudController
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    protected function getModelClass(): string
    {
        return config('thinkrix.models.user', \Thinkrix\Models\AdminUser::class);
    }

    protected function getResourceName(): string { return '用户'; }

    protected function getTable(): string
    {
        return config('thinkrix.tables.users', 'admin_users');
    }

    protected function getListWith(): array { return ['roles']; }
    protected function getExportFilenamePrefix(): string { return '用户列表'; }

    protected function getExportColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'username', 'title' => '用户名'],
            ['key' => 'nickname', 'title' => '昵称'],
            ['key' => 'email', 'title' => '邮箱'],
            ['key' => 'phone', 'title' => '手机号'],
            ['key' => 'roles', 'title' => '角色'],
            ['key' => 'status', 'title' => '状态'],
            ['key' => 'last_login_time', 'title' => '最后登录时间'],
            ['key' => 'created_at', 'title' => '创建时间'],
        ];
    }

    protected function applySearch($query): void
    {
        if ($keyword = $this->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                    ->whereOr('nickname', 'like', "%{$keyword}%")
                    ->whereOr('email', 'like', "%{$keyword}%")
                    ->whereOr('phone', 'like', "%{$keyword}%");
            });
        }
    }

    protected function applyFilters($query): void
    {
        if ($this->input('status') !== null && $this->input('status') !== '') {
            $query->where('status', $this->input('status'));
        }
    }

    protected function getStoreRules(): array
    {
        $table = $this->getTable();
        return [
            'username' => "require|max:20|unique:{$table}",
            'password' => 'require|min:6',
            'nickname' => 'max:20',
            'avatar' => 'max:255',
            'email' => 'email|max:255',
            'phone' => 'max:20',
            'remark' => 'max:255',
            'roles' => 'array',
            'status' => 'max:10',
        ];
    }

    protected function getUpdateRules(int $id): array
    {
        $table = $this->getTable();
        return [
            'username' => "require|max:20|unique:{$table},username,{$id}",
            'nickname' => 'max:20',
            'avatar' => 'max:255',
            'email' => 'email|max:255',
            'phone' => 'max:20',
            'remark' => 'max:255',
            'roles' => 'array',
        ];
    }

    protected function prepareStoreData(array $validated): array
    {
        return [
            'username' => $validated['username'],
            'password' => $validated['password'],
            'nickname' => $validated['nickname'] ?? null,
            'avatar' => $validated['avatar'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? '1',
            'remark' => $validated['remark'] ?? null,
        ];
    }

    protected function afterStore($model, array $validated): void
    {
        if (!empty($validated['roles'])) {
            $this->syncUserRoles($model, $validated['roles']);
        }
    }

    protected function afterUpdate($model, array $validated): void
    {
        if (isset($validated['roles'])) {
            $this->syncUserRoles($model, $validated['roles']);
        }
    }

    protected function syncUserRoles($user, array $roleNames): void
    {
        $roleModel = config('thinkrix.models.role', \Thinkrix\Models\Role::class);
        $userModel = $this->getModelClass();
        $roleIds = $roleModel::whereIn('name', $roleNames)
            ->where('guard_name', config('thinkrix.guard', 'admin'))
            ->column('id');

        app('db')->name('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', $userModel)
            ->delete();

        $data = [];
        foreach ($roleIds as $rid) {
            $data[] = [
                'role_id' => $rid,
                'model_type' => $userModel,
                'model_id' => $user->id,
            ];
        }
        if (!empty($data)) {
            app('db')->name('model_has_roles')->insertAll($data);
        }
    }

    protected function updateStatus(int $id): array
    {
        $model = $this->findOrFail($id);
        $data = request()->put();
        $this->validate($data, ['status' => 'require|in:0,1']);
        $model->status = $data['status'];
        $model->save();
        $this->afterStatusUpdate($model, (string) $data['status'] === '1');
        return success('状态更新成功', ['status' => $model->status]);
    }

    protected function afterStatusUpdate($model, bool $status): void
    {
        if (!$status) {
            $this->authService->revokeAllTokens($model);
        }
    }

    protected function beforeDelete($model): void
    {
        $this->authService->revokeAllTokens($model);
    }

    /**
     * 重置密码（action_type=reset_password）
     */
    protected function updateResetPassword(int $id): array
    {
        $model = $this->findOrFail($id);
        $data = request()->put();
        $this->validate($data, ['password' => 'require|min:6']);
        $model->password = $data['password'];
        $model->save();
        $this->authService->revokeAllTokens($model);
        return success('密码重置成功');
    }

    protected function listUi(): array
    {
        $userForm = OptForm::make('formData')
            ->fields([
                ['用户名', 'username', Input::make()->props(['placeholder' => '请输入用户名', 'disabled' => '{{ !!editingId }}'])],
                ['昵称', 'nickname', Input::make()->props(['placeholder' => '请输入昵称'])],
                ['邮箱', 'email', Input::make()->props(['placeholder' => '请输入邮箱'])],
                ['手机号', 'phone', Input::make()->props(['placeholder' => '请输入手机号'])],
                ['密码', 'password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请输入密码']), '', '!editingId'],
                ['角色', 'roles', Select::make()->props(['multiple' => true, 'placeholder' => '请选择角色', 'options' => '{{ roleOptions }}']), []],
                ['备注', 'remark', Input::make()->props(['type' => 'textarea', 'placeholder' => '请输入备注'])],
                ['状态', 'status', SwitchC::make()->props(['checkedValue' => '1', 'uncheckedValue' => '0']), '1'],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('确定'),
            ]);

        $resetPwdForm = OptForm::make()
            ->fields([
                ['新密码', 'newPassword', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请输入新密码（至少6位）'])],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('resetPwdVisible', false))->text('取消'),
                Button::make()->type('primary')->props(['loading' => '{{ resetPwdSubmitting }}'])->on('click', [
                    SetAction::make('resetPwdSubmitting', true),
                    FetchAction::make('/users/{{ resetPwdUserId }}')
                        ->put()
                        ->body(['action_type' => 'reset_password', 'password' => '{{ newPassword }}'])
                        ->then([CallAction::make('$message.success', ['密码重置成功']), SetAction::make('resetPwdVisible', false)])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "密码重置失败" }}'])])
                        ->finally([SetAction::make('resetPwdSubmitting', false)]),
                ])->text('确定'),
            ]);

        $schema = CrudPage::make('用户管理')
            ->apiPrefix('/users')
            ->columns($this->getTableColumns())
            ->scrollX(1200)->defaultPageSize(15)
            ->search([
                ['关键词', 'keyword', Input::make()->props(['placeholder' => '用户名/昵称/邮箱/手机号', 'clearable' => true])],
                ['状态', 'status', Select::make()->props(['placeholder' => '全部', 'clearable' => true, 'style' => ['width' => '120px'],
                    'options' => [['label' => '启用', 'value' => '1'], ['label' => '禁用', 'value' => '0']]])],
            ])
            ->toolbarLeft(['columnSelector', 'batchDelete',
                Button::make()->type('primary')->on('click', [
                    SetAction::batch(['editingId' => null, 'formData.username' => '', 'formData.nickname' => '', 'formData.email' => '', 'formData.phone' => '', 'formData.password' => '', 'formData.roles' => [], 'formData.remark' => '', 'formData.status' => '1', 'formVisible' => true]),
                ])->text('新增'),
            ])
            ->toolbarRight(['exportCurrent', 'exportAll', 'print'])
            ->data([
                'roleOptions' => $this->getRoleOptions(),
                'formData' => $userForm->getDefaultData(),
                'editingId' => null, 'submitting' => false,
                'resetPwdUserId' => null, 'resetPwdUserName' => '', 'newPassword' => '', 'resetPwdSubmitting' => false,
            ])
            ->methods([
                'handleSubmit' => [
                    SetAction::make('submitting', true),
                    IfAction::make('editingId')
                        ->then(FetchAction::make('{{ "/users/" + editingId }}')->put()->body('{{ formData }}')
                            ->then([CallAction::make('$message.success', ['更新成功']), SetAction::make('formVisible', false), CallAction::make('loadData')])
                            ->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)]))
                        ->else(FetchAction::make('/users')->post()->body('{{ formData }}')
                            ->then([CallAction::make('$message.success', ['创建成功']), SetAction::make('formVisible', false), CallAction::make('loadData')])
                            ->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)])),
                ],
            ])
            ->modal('form', '{{ editingId ? "编辑用户" : "新增用户" }}', $userForm, ['width' => '500px'])
            ->modal('resetPwd', '重置密码 - {{ resetPwdUserName }}', $resetPwdForm, ['width' => '400px']);

        return success($schema->build());
    }

    protected function getTableColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID', 'width' => 80],
            ['key' => 'username', 'title' => '用户名'],
            ['key' => 'nickname', 'title' => '昵称'],
            ['key' => 'email', 'title' => '邮箱'],
            ['key' => 'phone', 'title' => '手机号'],
            ['key' => 'roles', 'title' => '角色', 'width' => 150, 'slot' => [
                Space::make()->props(['size' => 'small'])->children([
                    Tag::make()->for('role in slotData.row.roles', '{{ role.id }}')->props(['type' => 'info', 'size' => 'small'])->children(['{{ role.title || role.name }}']),
                ]),
            ]],
            ['key' => 'status', 'title' => '状态', 'width' => 80, 'slot' => [
                SwitchC::make()->props(['value' => '{{ slotData.row.status === "1" }}'])
                    ->on('update:value', FetchAction::make('/users/{{ slotData.row.id }}')->put()->body(['action_type' => 'status', 'status' => '{{ $event ? "1" : "0" }}'])
                        ->then([CallAction::make('$message.success', ['状态更新成功']), CallAction::make('loadData')])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "状态更新失败" }}'])])),
            ]],
            ['key' => 'last_login_time', 'title' => '最后登录', 'width' => 180],
            ['key' => 'created_at', 'title' => '创建时间', 'width' => 180],
            ['key' => 'actions', 'title' => '操作', 'width' => 220, 'fixed' => 'right', 'slot' => [
                Space::make()->children([
                    Button::make()->size('small')->props(['type' => 'primary', 'text' => true])->on('click', [
                        SetAction::make('editingId', '{{ slotData.row.id }}'),
                        SetAction::make('formData.username', '{{ slotData.row.username }}'),
                        SetAction::make('formData.nickname', '{{ slotData.row.nickname || "" }}'),
                        SetAction::make('formData.email', '{{ slotData.row.email || "" }}'),
                        SetAction::make('formData.phone', '{{ slotData.row.phone || "" }}'),
                        SetAction::make('formData.roles', '{{ (slotData.row.roles || []).map(r => r.name) }}'),
                        SetAction::make('formData.remark', '{{ slotData.row.remark || "" }}'),
                        SetAction::make('formData.status', '{{ slotData.row.status }}'),
                        SetAction::make('formVisible', true),
                    ])->text('编辑'),
                    Button::make()->size('small')->props(['type' => 'warning', 'text' => true])->on('click', [
                        SetAction::make('resetPwdUserId', '{{ slotData.row.id }}'),
                        SetAction::make('resetPwdUserName', '{{ slotData.row.username }}'),
                        SetAction::make('newPassword', ''),
                        SetAction::make('resetPwdVisible', true),
                    ])->text('重置密码'),
                    Popconfirm::make()->props(['positiveText' => '确定', 'negativeText' => '取消'])
                        ->on('positive-click', FetchAction::make('/users/{{ slotData.row.id }}')->delete()
                            ->then([CallAction::make('$message.success', ['删除成功']), CallAction::make('loadData')])
                            ->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                        ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text('删除')])
                        ->children(['确定要删除用户 {{ slotData.row.username }} 吗？']),
                ]),
            ]],
        ];
    }

    protected function getRoleOptions(): array
    {
        $roleModel = config('thinkrix.models.role', \Thinkrix\Models\Role::class);
        $roles = $roleModel::where('status', true)
            ->where('guard_name', config('thinkrix.guard', 'admin'))
            ->select();
        $result = [];
        foreach ($roles as $role) {
            $result[] = ['label' => $role->title ?: $role->name, 'value' => $role->name];
        }
        return $result;
    }

    // ==================== 用户自助服务 ====================

    /**
     * 个人中心 UI（只读展示用户信息）
     */
    public function profileUi(): array
    {
        $user = $this->getUser();
        $infoItems = [
            ['用户名', $user->username],
            ['昵称', $user->nickname ?: '-'],
            ['邮箱', $user->email ?: '-'],
            ['手机', $user->phone ?: '-'],
            ['角色', implode(', ', $user->getRoleNames())],
            ['状态', $user->isActive() ? '启用' : '禁用'],
            ['最后登录', $user->last_login_time ?: '-'],
        ];
        $infoHtml = '';
        foreach ($infoItems as $item) {
            $infoHtml .= "<div style=\"display:flex;padding:8px 0;border-bottom:1px solid #f0f0f0\"><span style=\"width:80px;color:#999;flex-shrink:0\">{$item[0]}</span><span style=\"color:#333\">" . htmlspecialchars((string)$item[1]) . '</span></div>';
        }
        $schema = Html::div()->props(['style' => 'padding:12px'])->children([
            Html::div()->props(['style' => 'display:flex;align-items:center;gap:12px;margin-bottom:16px'])->children([
                Html::make('img')->props(['src' => $user->avatar ?: config('thinkrix.default_avatar', ''), 'style' => 'width:64px;height:64px;border-radius:50%;object-fit:cover;background:#f0f0f0']),
                Html::div()->children([
                    Html::div()->props(['style' => 'font-size:18px;font-weight:600'])->children([htmlspecialchars($user->nickname ?: $user->username)]),
                    Html::div()->props(['style' => 'font-size:13px;color:#999'])->children(["@{$user->username}"]),
                ]),
            ]),
            Html::div()->props(['style' => 'border-top:1px solid #f0f0f0'])->children([Html::div()->props(['innerHTML' => $infoHtml])]),
        ]);
        return success($schema->toArray());
    }

    /**
     * 账号设置 UI
     */
    public function settingsUi(): array
    {
        $user = $this->getUser();
        $form = OptForm::make('form')
            ->fields([
                ['昵称', 'nickname', Input::make()->props(['placeholder' => '请输入昵称']), $user->nickname],
                ['邮箱', 'email', Input::make()->props(['placeholder' => '请输入邮箱']), $user->email],
                ['手机号', 'phone', Input::make()->props(['placeholder' => '请输入手机号']), $user->phone],
            ])
            ->buttons([
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('保存'),
            ]);
        return success($form->toArray());
    }

    /**
     * 保存账号设置
     */
    public function updateSettings(): array
    {
        $user = $this->getUser();
        $data = request()->post();
        $this->validate($data, [
            'nickname' => 'max:20',
            'email' => 'email|max:255',
            'phone' => 'max:20',
        ]);
        $updates = array_intersect_key($data, array_flip(['nickname', 'email', 'phone']));
        if (!empty($updates)) {
            $user->save($updates);
        }
        return success('保存成功');
    }

    /**
     * 修改密码 UI
     */
    public function passwordUi(): array
    {
        $form = OptForm::make('form')
            ->fields([
                ['当前密码', 'current_password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请输入当前密码'])],
                ['新密码', 'new_password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请输入新密码（至少6位）'])],
                ['确认密码', 'confirm_password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请再次输入新密码'])],
            ])
            ->buttons([
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text('确认修改'),
            ]);
        return success($form->toArray());
    }

    /**
     * 修改密码
     */
    public function updatePassword(): array
    {
        $user = $this->getUser();
        $data = request()->post();
        $this->validate($data, [
            'current_password' => 'require',
            'new_password' => 'require|min:6',
            'confirm_password' => 'require',
        ]);

        if (!password_verify($data['current_password'], $user->password)) {
            return error('当前密码不正确');
        }
        if ($data['new_password'] !== $data['confirm_password']) {
            return error('两次输入的密码不一致');
        }

        $user->password = $data['new_password'];
        $user->save();

        // 撤销所有 Token，强制重新登录
        $this->authService->revokeAllTokens($user);

        return success('密码修改成功，请重新登录');
    }
}

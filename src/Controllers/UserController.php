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

    protected function getResourceName(): string { return __t('user.resource_name'); }

    protected function getTable(): string
    {
        return config('thinkrix.tables.users', 'admin_users');
    }

    protected function getListWith(): array { return ['roles']; }
    protected function getExportFilenamePrefix(): string { return __t('user.export_prefix'); }

    protected function getExportColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID'],
            ['key' => 'username', 'title' => __t('system.login.form.username')],
            ['key' => 'nickname', 'title' => __t('user.column.nickname')],
            ['key' => 'email', 'title' => __t('user.column.email')],
            ['key' => 'phone', 'title' => __t('system.login.form.phone')],
            ['key' => 'roles', 'title' => __t('user.column.roles')],
            ['key' => 'status', 'title' => __t('module.column.status')],
            ['key' => 'last_login_time', 'title' => __t('user.column.last_login_time')],
            ['key' => 'created_at', 'title' => __t('system.column.created_at')],
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
        return success(__t('crud.status_updated'), ['status' => $model->status]);
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
        return success(__t('auth.password_reset_ok'));
    }

    protected function listUi(): array
    {
        $userForm = OptForm::make('formData')
            ->fields([
                [__t('system.login.form.username'), 'username', Input::make()->props(['placeholder' => __t('user.placeholder.username'), 'disabled' => '{{ !!editingId }}'])],
                [__t('user.column.nickname'), 'nickname', Input::make()->props(['placeholder' => __t('user.placeholder.nickname')])],
                [__t('user.column.email'), 'email', Input::make()->props(['placeholder' => __t('user.placeholder.email')])],
                [__t('system.login.form.phone'), 'phone', Input::make()->props(['placeholder' => __t('user.placeholder.phone')])],
                [__t('system.login.form.password'), 'password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => __t('system.login.placeholder.password')]), '', '!editingId'],
                [__t('user.column.roles'), 'roles', Select::make()->props(['multiple' => true, 'placeholder' => __t('user.placeholder.roles'), 'options' => '{{ roleOptions }}']), []],
                [__t('user.form.remark'), 'remark', Input::make()->props(['type' => 'textarea', 'placeholder' => __t('user.placeholder.remark')])],
                [__t('module.column.status'), 'status', SwitchC::make()->props(['checkedValue' => '1', 'uncheckedValue' => '0']), '1'],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('formVisible', false))->text(__t('ui.button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text(__t('ui.button.confirm')),
            ]);

        $resetPwdForm = OptForm::make()
            ->fields([
                [__t('system.login.form.new_password'), 'newPassword', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => __t('user.placeholder.new_password')])],
            ])
            ->buttons([
                Button::make()->on('click', SetAction::make('resetPwdVisible', false))->text(__t('ui.button.cancel')),
                Button::make()->type('primary')->props(['loading' => '{{ resetPwdSubmitting }}'])->on('click', [
                    SetAction::make('resetPwdSubmitting', true),
                    FetchAction::make('/users/{{ resetPwdUserId }}')
                        ->put()
                        ->body(['action_type' => 'reset_password', 'password' => '{{ newPassword }}'])
                        ->then([CallAction::make('$message.success', [__t('crud.message.password_reset_ok')]), SetAction::make('resetPwdVisible', false)])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "密码重置失败" }}'])])
                        ->finally([SetAction::make('resetPwdSubmitting', false)]),
                ])->text(__t('ui.button.confirm')),
            ]);

        $schema = CrudPage::make(__t('user.page.title'))
            ->apiPrefix('/users')
            ->columns($this->getTableColumns())
            ->scrollX(1200)->defaultPageSize(15)
            ->search([
                [__t('dict.search.keyword'), 'keyword', Input::make()->props(['placeholder' => __t('user.search.placeholder'), 'clearable' => true])],
                [__t('module.column.status'), 'status', Select::make()->props(['placeholder' => __t('user.filter.all'), 'clearable' => true, 'style' => ['width' => '120px'],
                    'options' => [['label' => __t('ui.tag.enabled'), 'value' => '1'], ['label' => __t('ui.tag.disabled'), 'value' => '0']]])],
            ])
            ->toolbarLeft(['columnSelector', 'batchDelete',
                Button::make()->type('primary')->on('click', [
                    SetAction::batch(['editingId' => null, 'formData.username' => '', 'formData.nickname' => '', 'formData.email' => '', 'formData.phone' => '', 'formData.password' => '', 'formData.roles' => [], 'formData.remark' => '', 'formData.status' => '1', 'formVisible' => true]),
                ])->text(__t('permission.button.create')),
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
                            ->then([CallAction::make('$message.success', [__t('crud.message.updated')]), SetAction::make('formVisible', false), CallAction::make('loadData')])
                            ->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)]))
                        ->else(FetchAction::make('/users')->post()->body('{{ formData }}')
                            ->then([CallAction::make('$message.success', [__t('crud.message.created')]), SetAction::make('formVisible', false), CallAction::make('loadData')])
                            ->catch([CallAction::make('$message.error', ['{{ $error.message || "操作失败" }}'])])->finally([SetAction::make('submitting', false)])),
                ],
            ])
            ->modal('form', '{{ editingId ? "' . __t('user.title.edit') . '" : "' . __t('user.title.create') . '" }}', $userForm, ['width' => '500px'])
            ->modal('resetPwd', __t('user.title.reset_password'), $resetPwdForm, ['width' => '400px']);

        return success($schema->build());
    }

    protected function getTableColumns(): array
    {
        return [
            ['key' => 'id', 'title' => 'ID', 'width' => 80],
            ['key' => 'username', 'title' => __t('system.login.form.username')],
            ['key' => 'nickname', 'title' => __t('user.column.nickname')],
            ['key' => 'email', 'title' => __t('user.column.email')],
            ['key' => 'phone', 'title' => __t('system.login.form.phone')],
            ['key' => 'roles', 'title' => __t('user.column.roles'), 'width' => 150, 'slot' => [
                Space::make()->props(['size' => 'small'])->children([
                    Tag::make()->for('role in slotData.row.roles', '{{ role.id }}')->props(['type' => 'info', 'size' => 'small'])->children(['{{ role.title || role.name }}']),
                ]),
            ]],
            ['key' => 'status', 'title' => __t('module.column.status'), 'width' => 80, 'slot' => [
                SwitchC::make()->props(['value' => '{{ slotData.row.status === "1" }}'])
                    ->on('update:value', FetchAction::make('/users/{{ slotData.row.id }}')->put()->body(['action_type' => 'status', 'status' => '{{ $event ? "1" : "0" }}'])
                        ->then([CallAction::make('$message.success', [__t('crud.message.status_updated')]), CallAction::make('loadData')])
                        ->catch([CallAction::make('$message.error', ['{{ $error.message || "状态更新失败" }}'])])),
            ]],
            ['key' => 'last_login_time', 'title' => __t('user.column.last_login_time'), 'width' => 180],
            ['key' => 'created_at', 'title' => __t('system.column.created_at'), 'width' => 180],
            ['key' => 'actions', 'title' => __t('module.column.actions'), 'width' => 220, 'fixed' => 'right', 'slot' => [
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
                    ])->text(__t('permission.button.edit')),
                    Button::make()->size('small')->props(['type' => 'warning', 'text' => true])->on('click', [
                        SetAction::make('resetPwdUserId', '{{ slotData.row.id }}'),
                        SetAction::make('resetPwdUserName', '{{ slotData.row.username }}'),
                        SetAction::make('newPassword', ''),
                        SetAction::make('resetPwdVisible', true),
                    ])->text(__t('system.login.reset_password')),
                    Popconfirm::make()->props(['positiveText' => __t('ui.button.confirm'), 'negativeText' => __t('ui.button.cancel')])
                        ->on('positive-click', FetchAction::make('/users/{{ slotData.row.id }}')->delete()
                            ->then([CallAction::make('$message.success', [__t('crud.message.deleted')]), CallAction::make('loadData')])
                            ->catch([CallAction::make('$message.error', ['{{ $error.message || "删除失败" }}'])]))
                        ->slot('trigger', [Button::make()->size('small')->props(['type' => 'error', 'text' => true])->text(__t('permission.button.delete'))])
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
            [__t('system.login.form.username'), $user->username],
            [__t('user.column.nickname'), $user->nickname ?: '-'],
            [__t('user.column.email'), $user->email ?: '-'],
            [__t('user.column.phone'), $user->phone ?: '-'],
            [__t('user.column.roles'), implode(', ', $user->getRoleNames())],
            [__t('module.column.status'), $user->isActive() ? __t('ui.tag.enabled') : __t('ui.tag.disabled')],
            [__t('user.column.last_login_time'), $user->last_login_time ?: '-'],
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
                [__t('user.column.nickname'), 'nickname', Input::make()->props(['placeholder' => __t('user.placeholder.nickname')]), $user->nickname],
                [__t('user.column.email'), 'email', Input::make()->props(['placeholder' => __t('user.placeholder.email')]), $user->email],
                [__t('system.login.form.phone'), 'phone', Input::make()->props(['placeholder' => __t('user.placeholder.phone')]), $user->phone],
            ])
            ->buttons([
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text(__t('ui.button.save')),
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
        return success(__t('system.config_saved'));
    }

    /**
     * 修改密码 UI
     */
    public function passwordUi(): array
    {
        $form = OptForm::make('form')
            ->fields([
                ['当前密码', 'current_password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => __t('user.placeholder.current_password')])],
                [__t('system.login.form.new_password'), 'new_password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => __t('user.placeholder.new_password')])],
                [__t('system.login.form.confirm_pwd'), 'confirm_password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => __t('user.placeholder.confirm_password')])],
            ])
            ->buttons([
                Button::make()->type('primary')->props(['loading' => '{{ submitting }}'])->on('click', ['call' => 'handleSubmit'])->text(__t('user.button.confirm_modify')),
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
            return error(__t('auth.password_incorrect'));
        }
        if ($data['new_password'] !== $data['confirm_password']) {
            return error(__t('auth.password_mismatch'));
        }

        $user->password = $data['new_password'];
        $user->save();

        // 撤销所有 Token，强制重新登录
        $this->authService->revokeAllTokens($user);

        return success(__t('auth.password_changed'));
    }
}

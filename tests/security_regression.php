<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

function source(string $path): string
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException("Unable to read {$path}");
    }

    return $contents;
}

function assertSource(bool $condition, string $message): void
{
    global $failures;

    if (!$condition) {
        $failures[] = $message;
    }
}

$auth = source($root . '/src/Services/AuthService.php');
assertSource(!str_contains($auth, "->where('tokenable_type', AdminUser::class)"), 'Token queries must not hard-code AdminUser::class.');
assertSource(substr_count($auth, "->where('tokenable_type', \$userModel)") >= 5, 'Every token lookup/delete query must constrain the configured user model type.');
assertSource(str_contains($auth, "'tokenable_type' => \$userModel"), 'Created tokens must store the configured user model type.');

$notifications = source($root . '/src/Controllers/NotificationController.php');
assertSource(str_contains($notifications, "->where('guard_name', \$guard)"), 'Notification queries must constrain the current guard.');
assertSource(str_contains($notifications, 'protected function findOrFail(int $id)'), 'Notification show/update/delete/mark-read must use an ownership-scoped lookup.');
assertSource(str_contains($notifications, 'protected function batchDestroy(): array'), 'Batch notification deletion must use the ownership scope.');
assertSource(str_contains($notifications, "where('user_id', \$user->id)"), 'Notification ownership scope must constrain the current user.');
assertSource(substr_count($notifications, '$this->applyOwnershipScope($query);') >= 3, 'Notification list/read/delete operations must apply the ownership scope.');
assertSource(str_contains($notifications, "->where('user_id', \$this->getUser()->id)"), 'Mark-all-read must update only the current user rows.');
assertSource(
    str_contains($notifications, "\$validated['guard_name']")
        && str_contains($notifications, "\$validated['user_id']")
        && str_contains($notifications, "\$validated['from_user_id']")
        && str_contains($notifications, "\$validated['from_guard']"),
    'Notification updates must not reassign ownership fields.'
);
assertSource(str_contains($notifications, "\$validated['guard_name'] = config('thinkrix.guard', 'admin')"), 'Created notifications must use the current guard.');
assertSource(str_contains($notifications, "\$validated['from_user_id'] = \$this->getUser()->id"), 'Created notifications must record the authenticated sender.');
assertSource(str_contains($notifications, 'protected function getStoreRules(): array'), 'Notification create must validate and retain message fields.');
assertSource(str_contains($notifications, 'protected function getUpdateRules(int $id): array'), 'Notification update must validate editable message fields.');

$adminNotifications = source($root . '/src/Controllers/AdminNotificationController.php');
assertSource(str_contains($adminNotifications, 'guard_user_models'), 'Cross-guard notifications must resolve the target guard user model.');
assertSource(str_contains($adminNotifications, "'user_id' => \$userId"), 'Cross-guard notifications must create per-user messages.');
assertSource(!str_contains($adminNotifications, "'user_id' => null"), 'Cross-guard notifications must not create shared read-state rows.');
assertSource(str_contains($adminNotifications, "'from_guard' => \$currentGuard"), 'Sent notifications must record the sender guard.');
assertSource(str_contains($adminNotifications, "->where('from_guard', config('thinkrix.guard', 'admin'))"), 'Sent notification history must constrain the sender guard.');
assertSource(str_contains($notifications, '共享广播不支持单用户修改已读状态'), 'Legacy shared broadcasts must not share read-state mutations.');
assertSource(str_contains($notifications, '共享广播不能由单个接收用户删除'), 'Legacy shared broadcasts must not be deleted by one recipient.');

$routes = source($root . '/src/routes.php');
assertSource(substr_count($routes, 'CheckPermission::class') >= 4, 'High-risk administration routes must mount permission middleware.');
assertSource(str_contains($routes, 'system.setting.update'), 'Settings writes must require the existing settings update permission.');
assertSource(str_contains($routes, 'system.user.delete'), 'User deletion must require the existing user delete permission.');
assertSource(str_contains($routes, 'system.dict.delete'), 'Dictionary deletion must require the existing dictionary delete permission.');
assertSource(str_contains($routes, 'HandleApiException::class'), 'The outer Thinkrix route group must convert ApiException responses.');
assertSource(str_contains($routes, '*=system.user.update'), 'User update routes must define a default update permission.');
assertSource(str_contains($routes, 'status=system.user.status'), 'User status updates must require the status permission.');
assertSource(str_contains($routes, 'reset_password=system.user.password'), 'Password resets must require the password permission.');
assertSource(str_contains($routes, 'permissions=system.role.permissions'), 'Role permission sync must require the permissions permission.');
assertSource(str_contains($routes, 'sort=system.menu.sort'), 'Menu sorting must require the sort permission.');

$permissionMiddleware = source($root . '/src/Middleware/CheckPermission.php');
assertSource(str_contains($permissionMiddleware, "\$request->param('action_type'"), 'Permission middleware must inspect action_type.');
assertSource(str_contains($permissionMiddleware, "\$permissionMap['*']"), 'Permission middleware must support a safe fallback permission.');
assertSource(!str_contains($permissionMiddleware, 'userHasAnyPermission($user, $permissions)'), 'Permission middleware must not authorize against unrelated action permissions.');
assertSource(str_contains($routes, "'system.user.list'"), 'User reads and exports must require list permission.');
assertSource(str_contains($routes, "'system.permission.list'"), 'Permission reads must require list permission.');

$crud = source($root . '/src/Controllers/CrudController.php');
assertSource(substr_count($crud, '$this->applyResourceScope(') >= 4, 'CRUD list/read/update/delete operations must support resource isolation scopes.');

$adminUser = source($root . '/src/Models/AdminUser.php');
assertSource(str_contains($adminUser, "->where('guard_name', config('thinkrix.guard', 'admin'))"), 'Role relations must constrain the active guard.');
assertSource(str_contains($adminUser, "->where('p.guard_name', config('thinkrix.guard', 'admin'))"), 'Permission lookup must constrain the active guard.');

foreach (['RoleController.php', 'PermissionController.php', 'MenuController.php', 'NotificationCategoryController.php'] as $controller) {
    $contents = source($root . '/src/Controllers/' . $controller);
    assertSource(str_contains($contents, 'protected function applyResourceScope($query): void'), "{$controller} must scope record access by guard.");
}

assertSource(str_contains($auth, '$tokens->toArray()'), 'Token listing must support ThinkORM collections.');
assertSource(str_contains($auth, '$this->getTokenTable()'), 'Token queries must honor the configured table.');
assertSource(str_contains($auth, "'guard:' . config('thinkrix.guard', 'admin')"), 'Created tokens must bind to the current guard.');
assertSource(substr_count($auth, '$this->tokenAllowsCurrentGuard($tokenRecord)') >= 2, 'Token authentication must enforce the current guard.');
assertSource(str_contains($auth, "config('thinkrix.auth.require_guard_role', true)"), 'Login must support requiring a role in the current guard.');

$exceptionMiddlewarePath = $root . '/src/Middleware/HandleApiException.php';
assertSource(is_file($exceptionMiddlewarePath), 'ApiException conversion middleware must exist.');
if (is_file($exceptionMiddlewarePath)) {
    $exceptionMiddleware = source($exceptionMiddlewarePath);
    assertSource(str_contains($exceptionMiddleware, 'catch (ApiException $exception)'), 'Exception middleware must only catch ApiException.');
    assertSource(str_contains($exceptionMiddleware, 'return $exception->render();'), 'Exception middleware must return ApiException::render().');
    assertSource(str_contains($exceptionMiddleware, "'HTTP_ACCEPT' => 'application/json'"), 'Thinkrix API middleware must force framework array responses to JSON.');
}

if ($failures !== []) {
    fwrite(STDERR, "Security regression failures:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Security regression checks passed.\n";

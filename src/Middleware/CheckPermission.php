<?php

namespace Thinkrix\Middleware;

use Closure;
use think\Request;
use think\Response;
use Thinkrix\Services\PermissionService;

/**
 * CheckPermission - 权限检查中间件
 */
class CheckPermission
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$permissions 权限或 action_type=权限 映射；*=权限 为默认权限
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->thinkrix_user ?? null;

        if (!$user) {
            return json(['code' => 40001, 'msg' => '未认证', 'data' => null], 401);
        }

        $fallbackPermissions = [];
        $permissionMap = [];
        foreach ($permissions as $permission) {
            if (str_contains($permission, '=')) {
                [$action, $mappedPermission] = explode('=', $permission, 2);
                $permissionMap[$action] ??= [];
                if ($mappedPermission !== '') {
                    $permissionMap[$action][] = $mappedPermission;
                }
            } else {
                $fallbackPermissions[] = $permission;
            }
        }

        $actionType = (string) $request->param('action_type', '');
        $requiredPermissions = $permissionMap[$actionType]
            ?? $permissionMap['*']
            ?? $fallbackPermissions;

        if (!empty($requiredPermissions) && !$this->permissionService->userHasAnyPermission($user, $requiredPermissions)) {
            return json(['code' => 40003, 'msg' => '无权限访问', 'data' => null], 403);
        }

        return $next($request);
    }
}

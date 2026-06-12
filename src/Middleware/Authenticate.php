<?php

namespace Thinkrix\Middleware;

use Closure;
use think\Request;
use think\Response;
use Thinkrix\Services\AuthService;

/**
 * Authenticate - 认证中间件
 *
 * 验证请求中的 Bearer Token
 */
class Authenticate
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * 处理请求
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return json(['code' => 40001, 'msg' => '未认证', 'data' => null], 401);
        }

        $bearerToken = substr($authHeader, 7);
        $user = $this->authService->getUserFromToken($bearerToken);

        if (!$user) {
            return json(['code' => 40001, 'msg' => '未认证', 'data' => null], 401);
        }

        if (!$user->isActive()) {
            return json(['code' => 40101, 'msg' => '用户已禁用', 'data' => null], 403);
        }

        // 将用户对象绑定到请求
        $request->thinkrix_user = $user;

        return $next($request);
    }
}

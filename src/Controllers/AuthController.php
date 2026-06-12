<?php

namespace Thinkrix\Controllers;

use think\Request;
use Thinkrix\Services\AuthService;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * 用户登录
     */
    public function login(): array
    {
        $data = request()->post();
        $this->validate($data, [
            'username' => 'require|string',
            'password' => 'require|string',
        ]);

        $result = $this->authService->login($data['username'], $data['password']);

        if (!$result) {
            error('用户名或密码错误', null, 40001);
        }

        return success('登录成功', [
            'token' => $result['token']['plainTextToken'],
        ]);
    }

    /**
     * 用户登出
     */
    public function logout(): array
    {
        $this->authService->logout($this->getUser());
        return success('登出成功');
    }

    /**
     * 刷新 Token
     */
    public function refresh(): array
    {
        $token = $this->authService->refresh($this->getUser());
        return success('刷新成功', ['token' => $token['plainTextToken']]);
    }

    /**
     * 获取当前用户信息
     */
    public function user(): array
    {
        $user = $this->getUser();
        return success([
            'id' => $user->id,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getActivePermissionNames(),
        ]);
    }

    /**
     * 获取用户所有 Token
     */
    public function tokens(): array
    {
        $tokens = $this->authService->getTokens($this->getUser());
        return success($tokens);
    }

    /**
     * 撤销指定 Token
     */
    public function revokeToken(int $id): array
    {
        $result = $this->authService->revokeToken($this->getUser(), $id);
        if (!$result) {
            error('Token 不存在', null, 40004);
        }
        return success('撤销成功');
    }

    /**
     * 获取后台配置
     */
    public function config(): array
    {
        return success([
            'apiPrefix' => '/' . ltrim(config('thinkrix.api_prefix', 'api/admin'), '/'),
            'appTitle' => config('thinkrix.app_title', 'Thinkrix Admin'),
            'logo' => config('thinkrix.logo'),
        ]);
    }
}

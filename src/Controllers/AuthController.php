<?php

namespace Thinkrix\Controllers;

use think\Request;
use Thinkrix\Services\AuthService;
use Thinkrix\Models\Setting;
use Thinkrix\Services\TranslationService;

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
            error(__t('auth.failed'), null, 40001);
        }

        return success(__t('auth.login_ok'), [
            'token' => $result['token']['plainTextToken'],
        ]);
    }

    /**
     * 用户登出
     */
    public function logout(): array
    {
        $this->authService->logout($this->getUser());
        return success(__t('auth.logout_ok'));
    }

    /**
     * 刷新 Token
     */
    public function refresh(): array
    {
        $token = $this->authService->refresh($this->getUser());
        return success(__t('auth.refresh_ok'), ['token' => $token['plainTextToken']]);
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
            'locale' => $user->locale ?: config('thinkrix.locale', 'zh-CN'),
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
            error(__t('auth.token_not_found'), null, 40004);
        }
        return success(__t('auth.revoke_ok'));
    }

    /**
     * 获取后台配置
     */
    public function config(): array
    {
        $theme = Setting::fetchThemeConfig(config('thinkrix.theme', []));
        return success([
            'apiPrefix' => '/' . ltrim(config('thinkrix.api_prefix', 'api/admin'), '/'),
            'appTitle' => $theme['appTitle'] ?? 'Thinkrix Admin',
            'logo' => $theme['logo'] ?? '',
            'locale' => config('thinkrix.locale', 'zh-CN'),
            'fallbackLocale' => config('thinkrix.fallback_locale', 'en-US'),
            'languages' => app(TranslationService::class)->getLanguageOptions(),
            'translationsUrl' => '/translations',
        ]);
    }
}

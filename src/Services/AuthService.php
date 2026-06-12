<?php

namespace Thinkrix\Services;

use think\facade\Request;
use think\Model;

/**
 * AuthService - 认证服务
 *
 * 提供基于 Token 的认证功能，替代 Laravel Sanctum
 */
class AuthService extends BaseService
{
    /**
     * 用户模型类
     */
    protected function getUserModel(): string
    {
        return config('thinkrix.models.user', \Thinkrix\Models\AdminUser::class);
    }

    /**
     * 用户登录
     *
     * @param string $username
     * @param string $password
     * @return array|null
     */
    public function login(string $username, string $password): ?array
    {
        $userModel = $this->getUserModel();
        $user = $userModel::where('username', $username)->find();

        if (!$user || !password_verify($password, $user->password)) {
            return null;
        }

        if (!$user->isActive()) {
            return null;
        }

        if (config('thinkrix.auth.require_guard_role', true)
            && method_exists($user, 'getRoleNames')
            && $user->getRoleNames() === []) {
            return null;
        }

        // 更新最后登录信息
        $user->last_login_ip = Request::ip();
        $user->last_login_time = date('Y-m-d H:i:s');
        $user->save();

        // 生成新 Token
        $token = $this->createToken($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * 用户登出（撤销当前 Token）
     */
    public function logout(Model $user): bool
    {
        $token = $this->getCurrentToken();
        if ($token) {
            app('db')->name($this->getTokenTable())
                ->where('id', $token['id'])
                ->delete();
            return true;
        }
        return false;
    }

    /**
     * 刷新 Token
     */
    public function refresh(Model $user): array
    {
        // 撤销当前 Token
        $currentToken = $this->getCurrentToken();
        if ($currentToken) {
            app('db')->name($this->getTokenTable())
                ->where('id', $currentToken['id'])
                ->delete();
        }

        return $this->createToken($user);
    }

    /**
     * 获取用户所有 Token
     */
    public function getTokens(Model $user): array
    {
        $userModel = $this->getUserModel();
        $tokens = app('db')->name($this->getTokenTable())
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', $userModel)
            ->order('created_at', 'desc')
            ->select();

        return array_map(function ($token) {
            return [
                'id' => $token['id'],
                'name' => $token['name'],
                'last_used_at' => $token['last_used_at'] ?? null,
                'created_at' => $token['created_at'] ?? null,
            ];
        }, $tokens->toArray());
    }

    /**
     * 撤销指定 Token
     */
    public function revokeToken(Model $user, int $tokenId): bool
    {
        $userModel = $this->getUserModel();
        $deleted = app('db')->name($this->getTokenTable())
            ->where('id', $tokenId)
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', $userModel)
            ->delete();
        return $deleted > 0;
    }

    /**
     * 撤销用户所有 Token
     */
    public function revokeAllTokens(Model $user): int
    {
        $userModel = $this->getUserModel();
        return app('db')->name($this->getTokenTable())
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', $userModel)
            ->delete();
    }

    /**
     * 创建 Token
     */
    public function createToken(Model $user): array
    {
        $userModel = $this->getUserModel();
        $tokenPrefix = config('thinkrix.token.prefix', 'thinkrix');
        $expiration = config('thinkrix.token.expiration', 86400 * 7);

        if (config('thinkrix.token.revoke_previous_tokens', false)) {
            $this->revokeAllTokens($user);
        }

        $plainTextToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainTextToken);
        $name = $tokenPrefix . '_' . time();

        app('db')->name($this->getTokenTable())->insert([
            'tokenable_type' => $userModel,
            'tokenable_id' => $user->id,
            'name' => $name,
            'token' => $hashedToken,
            'abilities' => json_encode(['guard:' . config('thinkrix.guard', 'admin')]),
            'expires_at' => date('Y-m-d H:i:s', time() + $expiration),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $tokenId = app('db')->name($this->getTokenTable())->getLastInsID();

        return [
            'id' => $tokenId,
            'plainTextToken' => $tokenId . '|' . $plainTextToken,
            'name' => $name,
        ];
    }

    /**
     * 验证 Token 并返回用户
     */
    public function getUserFromToken(string $bearerToken): ?Model
    {
        // 解析 token: id|plaintext
        $parts = explode('|', $bearerToken);
        if (count($parts) !== 2) {
            return null;
        }

        $tokenId = (int) $parts[0];
        $plainText = $parts[1];
        $hashedToken = hash('sha256', $plainText);

        $userModel = $this->getUserModel();
        $tokenRecord = app('db')->name($this->getTokenTable())
            ->where('id', $tokenId)
            ->where('token', $hashedToken)
            ->where('tokenable_type', $userModel)
            ->find();

        if (!$tokenRecord) {
            return null;
        }
        if (!$this->tokenAllowsCurrentGuard($tokenRecord)) {
            return null;
        }

        // 检查是否过期
        if (!empty($tokenRecord['expires_at'])) {
            $expiresAt = strtotime($tokenRecord['expires_at']);
            if ($expiresAt < time()) {
                app('db')->name($this->getTokenTable())->where('id', $tokenId)->delete();
                return null;
            }
        }

        // 更新最后使用时间
        app('db')->name($this->getTokenTable())
            ->where('id', $tokenId)
            ->update(['last_used_at' => date('Y-m-d H:i:s')]);

        // 获取用户
        return $userModel::find($tokenRecord['tokenable_id']);
    }

    /**
     * 获取当前请求的 Token 记录
     */
    protected function getCurrentToken(): ?array
    {
        $authHeader = Request::header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $bearerToken = substr($authHeader, 7);
        $parts = explode('|', $bearerToken);
        if (count($parts) !== 2) {
            return null;
        }

        $tokenId = (int) $parts[0];
        $plainText = $parts[1];
        $hashedToken = hash('sha256', $plainText);

        $userModel = $this->getUserModel();
        $tokenRecord = app('db')->name($this->getTokenTable())
            ->where('id', $tokenId)
            ->where('token', $hashedToken)
            ->where('tokenable_type', $userModel)
            ->find();

        return $tokenRecord && $this->tokenAllowsCurrentGuard($tokenRecord) ? $tokenRecord : null;
    }

    protected function getTokenTable(): string
    {
        return config('thinkrix.token.table', 'personal_access_tokens');
    }

    protected function tokenAllowsCurrentGuard(array $tokenRecord): bool
    {
        $abilities = json_decode($tokenRecord['abilities'] ?? '[]', true);
        return is_array($abilities)
            && in_array('guard:' . config('thinkrix.guard', 'admin'), $abilities, true);
    }
}

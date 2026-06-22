<?php

use Thinkrix\Exceptions\ApiException;

if (!function_exists('success')) {
    /**
     * 成功响应
     *
     * @param string|array $msg 消息或数据（如果是数组则作为 data）
     * @param mixed $data 数据
     * @param int $code 状态码
     * @return array
     */
    function success(string|array $msg = 'success', mixed $data = null, int $code = 0): array
    {
        if (\is_array($msg)) {
            $data = $msg;
            $msg = 'success';
        }

        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}

if (!function_exists('error')) {
    /**
     * 错误响应（通过抛出异常触发）
     *
     * @param string $msg 错误消息
     * @param mixed $data 数据
     * @param int $code 错误码
     * @return never
     * @throws ApiException
     */
    function error(string $msg, mixed $data = null, int $code = 500): never
    {
        throw new ApiException($msg, $data, $code);
    }
}

if (!function_exists('__t')) {
    /**
     * 语言翻译
     *
     * 优先查找当前 locale，找不到则回退到默认语言（zh-cn），
     * 再找不到则返回 key 本身。
     * 支持参数替换：__t('auth.failed')、__t('welcome :name', ['name' => '张三'])
     *
     * @param string $key     翻译键名
     * @param array  $replace 替换参数
     * @return string
     */
    function __t(string $key, array $replace = []): string
    {
        try {
            $locale = app()->lang->getLangSet() ?: config('thinkrix.locale', 'zh-CN');
            return app(\Thinkrix\Services\TranslationService::class)->translate($key, $replace, $locale);
        } catch (\Throwable $e) {
            return $key;
        }
    }
}

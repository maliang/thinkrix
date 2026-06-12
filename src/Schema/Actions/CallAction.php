<?php

namespace Thinkrix\Schema\Actions;

/**
 * CallAction - 调用方法动作
 *
 * 对应 vschema 的 CallAction 类型
 */
class CallAction implements ActionInterface
{
    /**
     * 需要自动补全 $methods. 前缀的内置方法前缀
     */
    protected static array $builtinPrefixes = [
        '$message.',
        '$dialog.',
        '$notification.',
        '$loadingBar.',
        '$nav.',
        '$tab.',
        '$window.',
        '$download',
    ];

    public function __construct(
        protected string $method,
        protected array $args = []
    ) {
        $this->method = $this->normalizeMethod($method);
    }

    /**
     * 创建实例
     */
    public static function make(string $method, array $args = []): static
    {
        return new static($method, $args);
    }

    /**
     * 规范化方法名，自动补全 $methods. 前缀
     */
    protected function normalizeMethod(string $method): string
    {
        if (str_starts_with($method, '$methods.')) {
            return $method;
        }

        foreach (self::$builtinPrefixes as $prefix) {
            if (str_starts_with($method, $prefix)) {
                return '$methods.' . $method;
            }
        }

        return $method;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        $result = ['call' => $this->method];

        if (!empty($this->args)) {
            $result['args'] = $this->args;
        }

        return $result;
    }
}

<?php

namespace Thinkrix\Schema\Actions;

/**
 * SetAction - 设置值动作
 *
 * 对应 vschema 的 SetAction 类型
 */
class SetAction implements ActionInterface
{
    protected ?array $batchData = null;

    public function __construct(
        protected string $path = '',
        protected mixed $value = null
    ) {}

    /**
     * 创建单个赋值实例
     */
    public static function make(string $path, mixed $value): static
    {
        return new static($path, $value);
    }

    /**
     * 创建批量赋值实例
     */
    public static function batch(array $data): static
    {
        $instance = new static();
        $instance->batchData = $data;
        return $instance;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        if ($this->batchData !== null) {
            $actions = [];
            foreach ($this->batchData as $path => $value) {
                $actions[] = [
                    'set' => $path,
                    'value' => $value,
                ];
            }
            return $actions;
        }

        return [
            'set' => $this->path,
            'value' => $this->value,
        ];
    }

    /**
     * 判断是否为批量模式
     */
    public function isBatch(): bool
    {
        return $this->batchData !== null;
    }
}

<?php

namespace Thinkrix\Schema\Actions;

/**
 * ScriptAction - 脚本执行动作
 *
 * 对应 vschema 的 ScriptAction 类型
 */
class ScriptAction implements ActionInterface
{
    public function __construct(
        protected string $script
    ) {}

    public static function make(string $script): static
    {
        return new static($script);
    }

    public function toArray(): array
    {
        return ['script' => $this->script];
    }
}

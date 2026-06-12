<?php

namespace Thinkrix\Schema\Actions;

/**
 * IfAction - 条件判断动作
 *
 * 对应 vschema 的 IfAction 类型
 */
class IfAction implements ActionInterface
{
    protected string $condition;
    protected ActionInterface|array|null $then = null;
    protected ActionInterface|array|null $else = null;

    public function __construct(string $condition)
    {
        $this->condition = $condition;
    }

    public static function make(string $condition): static
    {
        return new static($condition);
    }

    public function then(ActionInterface|array $actions): static
    {
        $this->then = $actions;
        return $this;
    }

    public function else(ActionInterface|array $actions): static
    {
        $this->else = $actions;
        return $this;
    }

    protected function convertActions(ActionInterface|array|null $actions): mixed
    {
        if ($actions === null) {
            return null;
        }

        if ($actions instanceof ActionInterface) {
            return $actions->toArray();
        }

        return array_map(
            fn($a) => $a instanceof ActionInterface ? $a->toArray() : $a,
            $actions
        );
    }

    public function toArray(): array
    {
        $result = [
            'if' => $this->condition,
            'then' => $this->convertActions($this->then),
        ];

        $elseResult = $this->convertActions($this->else);
        if ($elseResult !== null) {
            $result['else'] = $elseResult;
        }

        return $result;
    }
}

<?php

namespace Thinkrix\Schema\Actions;

/**
 * EmitAction - 触发事件动作
 *
 * 对应 vschema 的 EmitAction 类型
 */
class EmitAction implements ActionInterface
{
    protected string $event;
    protected mixed $payload = null;

    public function __construct(string $event)
    {
        $this->event = $event;
    }

    public static function make(string $event): static
    {
        return new static($event);
    }

    public function payload(mixed $payload): static
    {
        $this->payload = $payload;
        return $this;
    }

    public function toArray(): array
    {
        $result = ['emit' => $this->event];

        if ($this->payload !== null) {
            $result['payload'] = $this->payload;
        }

        return $result;
    }
}

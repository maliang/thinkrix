<?php

namespace Thinkrix\Schema\Actions;

/**
 * CopyAction - 复制到剪贴板动作
 *
 * 对应 vschema 的 CopyAction 类型
 */
class CopyAction implements ActionInterface
{
    protected string $content;
    protected array $then = [];
    protected array $catch = [];

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public static function make(string $content): static
    {
        return new static($content);
    }

    public function then(ActionInterface|array $actions): static
    {
        $this->then = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    public function catch(ActionInterface|array $actions): static
    {
        $this->catch = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    public function toArray(): array
    {
        $result = ['copy' => $this->content];

        if (!empty($this->then)) {
            $result['then'] = array_map(
                fn($a) => $a instanceof ActionInterface ? $a->toArray() : $a,
                $this->then
            );
        }

        if (!empty($this->catch)) {
            $result['catch'] = array_map(
                fn($a) => $a instanceof ActionInterface ? $a->toArray() : $a,
                $this->catch
            );
        }

        return $result;
    }
}

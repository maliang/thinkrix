<?php

namespace Thinkrix\Schema\Components\Business;

use Thinkrix\Schema\Components\Component;

/**
 * FlowEditor - 流程编辑器组件
 */
class FlowEditor extends Component
{
    public function __construct()
    {
        parent::__construct('FlowEditor');
    }

    public static function make(): static
    {
        return new static();
    }

    public function nodes(array|string $nodes): static
    {
        return $this->props(is_string($nodes) ? ['nodes' => "{{ {$nodes} }}"] : ['nodes' => $nodes]);
    }

    public function edges(array|string $edges): static
    {
        return $this->props(is_string($edges) ? ['edges' => "{{ {$edges} }}"] : ['edges' => $edges]);
    }

    public function nodeTypes(array $types): static
    {
        return $this->props(['nodeTypes' => $types]);
    }

    public function edgeTypes(array $types): static
    {
        return $this->props(['edgeTypes' => $types]);
    }

    public function readonly(bool $readonly = true): static
    {
        return $this->props(['readonly' => $readonly]);
    }

    public function height(string|int $height): static
    {
        return $this->props(['height' => $height]);
    }

    public function minimap(bool $show = true): static
    {
        return $this->props(['minimap' => $show]);
    }

    public function controls(bool $show = true): static
    {
        return $this->props(['controls' => $show]);
    }

    public function background(bool|array $background = true): static
    {
        return $this->props(['background' => $background]);
    }

    public function connectionLineStyle(array $style): static
    {
        return $this->props(['connectionLineStyle' => $style]);
    }

    public function defaultEdgeOptions(array $options): static
    {
        return $this->props(['defaultEdgeOptions' => $options]);
    }

    public function fitView(bool $fit = true): static
    {
        return $this->props(['fitView' => $fit]);
    }

    public function snapToGrid(bool $snap = true): static
    {
        return $this->props(['snapToGrid' => $snap]);
    }
}

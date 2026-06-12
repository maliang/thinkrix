<?php

namespace Thinkrix\Schema\Components\Business;

use Thinkrix\Schema\Components\Component;

/**
 * IconPicker - 图标选择器组件
 */
class IconPicker extends Component
{
    public function __construct()
    {
        parent::__construct('IconPicker');
    }

    public static function make(): static
    {
        return new static();
    }

    public function value(string $value): static
    {
        return $this->props(['value' => "{{ {$value} }}"]);
    }

    public function icons(array $icons): static
    {
        return $this->props(['icons' => $icons]);
    }

    public function placeholder(string $text): static
    {
        return $this->props(['placeholder' => $text]);
    }

    public function popoverWidth(string|int $width): static
    {
        return $this->props(['popoverWidth' => $width]);
    }

    public function clearable(bool $clearable = true): static
    {
        return $this->props(['clearable' => $clearable]);
    }
}

<?php

namespace Thinkrix\Schema\Components\Business;

use Thinkrix\Schema\Components\Component;

/**
 * RichEditor - 富文本编辑器组件
 */
class RichEditor extends Component
{
    public function __construct()
    {
        parent::__construct('RichEditor');
    }

    public static function make(): static
    {
        return new static();
    }

    public function value(string $value): static
    {
        return $this->props(['value' => "{{ {$value} }}"]);
    }

    public function height(string|int $height): static
    {
        return $this->props(['height' => $height]);
    }

    public function placeholder(string $text): static
    {
        return $this->props(['placeholder' => $text]);
    }

    public function readonly(bool $readonly = true): static
    {
        return $this->props(['readonly' => $readonly]);
    }

    public function toolbar(array $items): static
    {
        return $this->props(['toolbar' => $items]);
    }

    public function theme(string $theme): static
    {
        return $this->props(['theme' => $theme]);
    }

    public function uploadUrl(string $url): static
    {
        return $this->props(['uploadUrl' => $url]);
    }

    public function uploadHeaders(array $headers): static
    {
        return $this->props(['uploadHeaders' => $headers]);
    }

    public function imageAccept(string $accept): static
    {
        return $this->props(['imageAccept' => $accept]);
    }

    public function imageMaxSize(int $size): static
    {
        return $this->props(['imageMaxSize' => $size]);
    }

    public function imageMaxCount(int $count): static
    {
        return $this->props(['imageMaxCount' => $count]);
    }

    public function locale(string $locale): static
    {
        return $this->props(['locale' => $locale]);
    }

    public function editorType(string $type): static
    {
        return $this->props(['editorType' => $type]);
    }
}

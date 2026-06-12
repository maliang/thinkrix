<?php

namespace Thinkrix\Schema\Components\Business;

use Thinkrix\Schema\Components\Component;

/**
 * MarkdownEditor - Markdown 编辑器组件
 */
class MarkdownEditor extends Component
{
    public function __construct()
    {
        parent::__construct('MarkdownEditor');
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

    public function preview(bool $preview = true): static
    {
        return $this->props(['preview' => $preview]);
    }

    public function toolbar(array $items): static
    {
        return $this->props(['toolbar' => $items]);
    }

    public function theme(string $theme): static
    {
        return $this->props(['theme' => $theme]);
    }

    public function language(string $lang): static
    {
        return $this->props(['language' => $lang]);
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
}

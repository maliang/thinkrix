<?php

namespace Thinkrix\Schema\Components\Common;

use Thinkrix\Schema\Components\Component;

/**
 * HeaderCustomItem - 自定义导航栏图标组件
 *
 * 用于在导航栏右侧渲染用户自定义的图标按钮，
 * 支持徽标计数、提示文字、点击跳转或弹窗。
 * 由 config('thinkrix.header.custom_items') 配置驱动。
 */
class HeaderCustomItem extends Component
{
    public function __construct()
    {
        parent::__construct('HeaderCustomItem');
    }

    public static function make(): static
    {
        return new static();
    }

    /**
     * 设置图标（Iconify 图标名）
     */
    public function icon(string $name): static
    {
        return $this->props(['icon' => $name]);
    }

    /**
     * 设置鼠标悬停提示
     */
    public function tooltip(string $text): static
    {
        return $this->props(['tooltip' => $text]);
    }

    /**
     * 设置徽标配置（如绑定通知类型未读数）
     */
    public function badge(array $config): static
    {
        return $this->props(['badge' => $config]);
    }

    /**
     * 设置点击行为类型：link / modal / drawer / none
     */
    public function click(string $type): static
    {
        return $this->props(['click' => $type]);
    }

    /**
     * 设置点击目标（跳转 URL 或弹窗 API 地址）
     */
    public function clickTarget(string $target): static
    {
        return $this->props(['clickTarget' => $target]);
    }

    public function target(string $target): static
    {
        return $this->props(['target' => $target]);
    }

    /**
     * 设置 Schema API 地址
     * 接口返回任意 schema 节点，完全由 schema 控制渲染内容
     * 可用于下拉菜单、开关、Popover 自定义内容等复杂交互
     */
    public function schemaApi(string $api): static
    {
        return $this->props(['schemaApi' => $api]);
    }
}

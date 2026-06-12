<?php

namespace Thinkrix\Schema\Components\NaiveUI;

use Thinkrix\Schema\Components\Component;

/**
 * NCarouselItem - Naive UI 轮播图项组件
 */
class CarouselItem extends Component
{
    public function __construct()
    {
        parent::__construct('NCarouselItem');
    }

    public static function make(): static
    {
        return new static();
    }
}

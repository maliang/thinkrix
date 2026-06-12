<?php

namespace Thinkrix\Schema\Components\NaiveUI;

use Thinkrix\Schema\Components\Component;

/**
 * NHr - Naive UI 水平分割线组件
 */
class Hr extends Component
{
    public function __construct()
    {
        parent::__construct('NHr');
    }

    public static function make(): static
    {
        return new static();
    }
}

<?php

namespace Thinkrix\Schema\Components\NaiveUI;

use Thinkrix\Schema\Components\Component;

/**
 * NCalendar - Naive UI 日历组件
 */
class Calendar extends Component
{
    public function __construct()
    {
        parent::__construct('NCalendar');
    }

    public static function make(): static
    {
        return new static();
    }

    public function isDateDisabled(string $fn): static
    {
        return $this->props(['isDateDisabled' => $fn]);
    }
}

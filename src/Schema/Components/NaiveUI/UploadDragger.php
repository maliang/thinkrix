<?php

namespace Thinkrix\Schema\Components\NaiveUI;

use Thinkrix\Schema\Components\Component;

/**
 * NUploadDragger - Naive UI 上传拖拽区域组件
 */
class UploadDragger extends Component
{
    public function __construct()
    {
        parent::__construct('NUploadDragger');
    }

    public static function make(): static
    {
        return new static();
    }
}

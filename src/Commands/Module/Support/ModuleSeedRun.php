<?php

namespace Thinkrix\Commands\Module\Support;

class ModuleSeedRun extends \think\migration\command\seed\Run
{
    public function __construct(protected string $seedPath)
    {
        parent::__construct();
    }

    protected function getPath(): string
    {
        return $this->seedPath;
    }
}

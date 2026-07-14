<?php

namespace Thinkrix\Controllers;

use Thinkrix\Services\ModulePublishService;

/** 承接本地模块与项目发布路由。 */
class ModulePublishController extends Controller
{
    public function __construct(private readonly ModulePublishService $publishing) {}
    public function module(string $name): array { return $this->publishing->publishLocal($name); }
    public function project(): array { return $this->publishing->publishProject(); }
}

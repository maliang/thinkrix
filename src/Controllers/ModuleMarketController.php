<?php

namespace Thinkrix\Controllers;

use Thinkrix\Schema\Pages\ModuleMarketSchema;
use Thinkrix\Services\ModuleMarketService;

/** 承接模块市场查询与安装路由。 */
class ModuleMarketController extends Controller
{
    public function __construct(private readonly ModuleMarketService $market, private readonly ModuleMarketSchema $schema) {}
    public function ui(): array { return $this->schema->market(); }
    public function modules(): array { return $this->market->modules(request()->param()); }
    public function projects(): array { return $this->market->projects(request()->param()); }
    public function installModule(string $id): array { return $this->market->installModule($id); }
    public function installProject(string $id): array { return $this->market->installProject($id); }
}

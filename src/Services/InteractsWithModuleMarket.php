<?php

namespace Thinkrix\Services;

use Thinkrix\Modules\Registry\RegistryClient;

/** 提供模块市场服务共享的配置与客户端创建逻辑。 */
trait InteractsWithModuleMarket
{
    protected function registryUrl(): string
    {
        return rtrim((string) config('thinkrix.module_market.url', ''), '/');
    }

    protected function registryAuthKey(): string
    {
        return trim((string) config('thinkrix.module_market.auth_key', ''));
    }

    protected function registryClient(): RegistryClient
    {
        return new RegistryClient($this->registryUrl(), $this->registryAuthKey(), (int) config('thinkrix.module_market.timeout', 30));
    }
}

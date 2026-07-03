# 自定义组件

Thinkrix 后端 schema 只能引用 Trix 已注册的前端组件。新增真正的前端组件时，应修改 `trix/src`，构建后再同步到 Thinkrix 资源。

## 后端封装已有组件

如果 Trix 中已经注册了组件，可以在 Thinkrix 后端写 PHP 封装：

```php
namespace App\Schema\Components;

use Thinkrix\Schema\Components\Component;

class MyWidget extends Component
{
    public function __construct()
    {
        parent::__construct('MyWidget');
    }

    public static function make(): static
    {
        return new static();
    }
}
```

## 前端新增组件流程

1. 在 `trix/src` 创建 Vue 组件。
2. 在 `trix/src/plugins/json-renderer.ts` 注册组件名。
3. 构建 Trix。
4. 将构建产物同步到 `lartrix-think/resources/admin`。
5. 在 Thinkrix PHP 中新增对应 schema 封装。

不要直接修改 `lartrix-think/resources/admin` 的构建产物。


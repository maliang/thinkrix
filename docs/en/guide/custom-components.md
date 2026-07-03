# Custom Components

Thinkrix backend schema can only reference frontend components registered in Trix. To add a real frontend component, edit `trix/src`, build it, and sync the built assets.

## PHP Wrapper For Existing Frontend Component

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

## Frontend Component Flow

1. Create the Vue component in `trix/src`.
2. Register it in `trix/src/plugins/json-renderer.ts`.
3. Build Trix.
4. Sync the build output to `lartrix-think/resources/admin`.
5. Add a Thinkrix PHP schema wrapper if desired.

Do not edit built files under `lartrix-think/resources/admin` directly.


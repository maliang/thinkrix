# Component Overview

Thinkrix PHP schema components live in `src/Schema/Components` and generate JSON Schema that Trix/vschema-ui can render.

## Groups

- `NaiveUI`: wrappers for Naive UI components such as `Button`, `Input`, `DataTable`, and `Card`.
- `Business`: composed business widgets such as `CrudPage`, `OptForm`, `OneImgUp`, and `MarkdownEditor`.
- `Common`: admin layout widgets such as `HeaderNotification`, `HeaderCustomItem`, and `ThemeButton`.
- `Custom`: Trix custom components such as `SvgIcon`, `Icon`, `Html`, and `VueECharts`.

## Basic Usage

```php
use Thinkrix\Schema\Components\NaiveUI\Card;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Actions\CallAction;

return Card::make()
    ->props(['title' => 'Demo'])
    ->children([
        Button::make()
            ->type('primary')
            ->text('Save')
            ->on('click', CallAction::make('$message.success', ['Saved'])),
    ])
    ->toArray();
```

All components should finally output arrays through `toArray()` or business component `build()`.


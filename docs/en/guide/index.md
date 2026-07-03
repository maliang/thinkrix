# Introduction

Thinkrix is the ThinkPHP port of Lartrix. It shares the Trix frontend and vschema-ui renderer, and provides admin users, roles, permissions, menus, modules, system settings, internationalization, and realtime messages for ThinkPHP projects.

## Documentation Map

- [Installation](./installation.md)
- [Configuration](./configuration.md)
- [Component Overview](./components/index.md)
- [Action Overview](./actions/index.md)
- [Modules](./modules.md)
- [Internationalization](./i18n.md)
- [Data Dictionary](./dict.md)
- [Schema UI](./schema.md)
- [Notifications and Realtime](./notifications.md)
- [Theme and Site Settings](./theme.md)
- [Custom Components](./custom-components.md)

## Relationship With Lartrix

Thinkrix and Lartrix keep the frontend protocol aligned where possible:

- Same Trix JSON schema page model, including `CrudPage`, `DataTable`, `OptForm`, and `OneImgUp`.
- Same public config keys such as `appTitle`, `logo`, `realtime.behaviors`, `header.custom_items`, and `badge`.
- Same i18n endpoints: `/api/admin/translations` and `/api/admin/locale`.
- Same notification polling shape: `unread_count`, `unread_count_by_type`, and `messages`.

Framework internals differ:

- Lartrix uses Laravel, Sanctum, Spatie Permission, and nwidart/laravel-modules.
- Thinkrix uses ThinkPHP configuration, routes, middleware, models, and its own module loader.

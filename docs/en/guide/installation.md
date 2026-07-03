# Installation

## Requirements

- PHP 8.1+
- ThinkPHP 8
- MySQL or MariaDB
- Composer

## Install The Package

```bash
composer require lartrix/lartrix-think
```

In local monorepo development, the host ThinkPHP application usually references `lartrix-think` through a Composer path repository.

## Publish Assets

```bash
php think thinkrix:publish
```

Publishing includes:

- Admin frontend assets to `public/admin`
- Default configuration files
- Language files and required static assets

When changing the shared frontend, edit `trix`, build it, then publish or sync the built assets. Do not edit built admin assets directly.

## Initialize Database

```bash
php think thinkrix:install
```

The installer:

1. Publishes frontend assets.
2. Runs migrations, with a full table-creation fallback if migration constraints fail.
3. Seeds base users, roles, permissions, menus, settings, and notification categories.

After installation, visit `THINKRIX_PATH`, which defaults to `/admin`.

## Common Issues

### Foreign Key Migration Failure

Some MySQL environments or existing tables may fail when creating foreign keys. The installer falls back to full table creation; verify that the base tables were created successfully.

### Controller Not Found: app\\controller\\Api

Confirm Thinkrix routes are loaded and the request path includes the configured `api_prefix`, which defaults to `/api/admin`.


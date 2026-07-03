# API Overview

The default API prefix is `/api/admin`. Authenticated endpoints use Thinkrix token middleware.

## Authentication

- `POST /api/admin/login`
- `POST /api/admin/logout`
- `GET /api/admin/user`

After login, Trix stores the token and sends `Authorization: Bearer {token}` for regular API calls, schema requests, and uploads.

## System

- `GET /api/admin/system/config`
- `GET /api/admin/translations`
- `POST /api/admin/locale`
- `GET /api/admin/settings`
- `POST /api/admin/settings`

## Upload

- `POST /api/admin/upload/image`

Image upload requires authentication and returns an image URL. Trix `NUpload` and `OneImgUp` automatically attach auth headers.

## Notifications

- `GET /api/admin/notifications`
- `GET /api/admin/notifications/poll`
- `POST /api/admin/notifications/{id}/mark-read`
- `POST /api/admin/notifications/mark-all-read`

`poll` powers realtime updates and badge counts.

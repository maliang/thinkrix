# API 概览

默认 API 前缀为 `/api/admin`。所有需要登录的接口都通过 Thinkrix token 中间件认证。

## 认证

- `POST /api/admin/login`
- `POST /api/admin/logout`
- `GET /api/admin/user`

登录成功后前端会保存 token，普通 API、schema 请求和上传请求统一携带 `Authorization: Bearer {token}`。

## 系统

- `GET /api/admin/system/config`
- `GET /api/admin/translations`
- `POST /api/admin/locale`
- `GET /api/admin/settings`
- `POST /api/admin/settings`

## 上传

- `POST /api/admin/upload/image`

图片上传需要认证，返回图片 URL。Trix 的 `NUpload` / `OneImgUp` 会自动带认证头。

## 通知

- `GET /api/admin/notifications`
- `GET /api/admin/notifications/poll`
- `POST /api/admin/notifications/{id}/mark-read`
- `POST /api/admin/notifications/mark-all-read`

`poll` 用于实时刷新和角标统计，返回未读总数、按类型未读数和新增消息。


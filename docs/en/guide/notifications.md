# Notifications And Realtime

Thinkrix includes notification categories, message lists, read state, polling or WebSocket realtime updates, and menu/header badges.

## Configuration

```php
'realtime' => [
    'enabled' => true,
    'enable_notification' => true,
    'driver' => 'polling',
    'polling' => [
        'interval' => 15000,
        'api' => '/notifications/poll',
    ],
    'behaviors' => [
        'audit.pending' => [
            'notify' => false,
            'actions' => [
                ['type' => 'sound', 'src' => '/sounds/audit.mp3', 'times' => 3],
            ],
        ],
    ],
],
```

## Polling

Default endpoint:

```http
GET /api/admin/notifications/poll?since_id=0
```

Response:

```json
{
  "unread_count": 5,
  "unread_count_by_type": {
    "mobile.recharge.pending": 2
  },
  "has_new": true,
  "messages": []
}
```

Badge counts come from `unread_count_by_type`, not the current notification list page.

## Behavior Actions

Built-in actions:

- `sound`: play a sound. `times` controls repeat count.
- `notification`: show a frontend notification.

Messages may override configured behavior through `extra.actions`.

Browsers block autoplay before user interaction. Trix unlocks audio after the first user gesture and queues sounds until then.

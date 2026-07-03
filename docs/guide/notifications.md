# 通知与实时消息

Thinkrix 的消息系统包括通知分类、消息列表、已读状态、实时轮询或 WebSocket，以及菜单/导航栏角标。

## 配置

```php
'notification' => [
    'category_model' => \Thinkrix\Models\NotificationCategory::class,
    'message_model' => \Thinkrix\Models\NotificationMessage::class,
    'guard_user_models' => [
        'admin' => \Thinkrix\Models\AdminUser::class,
    ],
],

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

## 轮询接口

默认接口：

```http
GET /api/admin/notifications/poll?since_id=0
```

返回结构：

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

菜单和导航栏角标读取 `unread_count_by_type`，不会因为通知列表分页而统计错误。业务处理完一条消息后，应更新消息已读状态或业务状态，并让下一次轮询返回新的数量。

## 行为动作

内置动作：

- `sound`：播放指定声音，`times` 控制播放次数。
- `notification`：弹出前端通知。

消息自身也可以通过 `extra.actions` 覆盖配置行为：

```json
{
  "type": "audit.pending",
  "title": "新的审核任务",
  "extra": {
    "actions": [
      { "type": "sound", "src": "/voice/audit.mp3", "times": 3 }
    ]
  }
}
```

浏览器自动播放受限，Trix 会在首次用户交互后解锁音频；解锁前到达的声音会排队。

## 导航栏角标

```php
[
    'icon' => 'mdi:account-card-outline',
    'tooltip' => '待处理实名',
    'badge' => [
        'source' => 'notification',
        'types' => ['mobile.kyc.pending'],
        'mode' => 'count',
        'max' => 99,
        'color' => '#18a058',
    ],
    'click' => 'route',
    'click_target' => '/members/kyc',
]
```

点击 `route` 类型导航项会执行后台内部跳转，效果与点击菜单一致。


<?php

namespace Thinkrix\Models;

use think\Model;
use think\facade\Cache;

/**
 * Setting - 系统设置模型
 *
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $title
 * @property string $type
 * @property string|null $value
 * @property string|null $default_value
 * @property string|null $description
 * @property int $sort
 * @property string $created_at
 * @property string $updated_at
 */
class Setting extends Model
{
    protected $table = 'admin_settings';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'sort' => 'integer',
    ];

    protected $fillable = [
        'group', 'key', 'title', 'type', 'value', 'default_value', 'description', 'sort',
    ];

    /**
     * 获取设置值（带缓存）
     */
    public static function fetchValue(string $key, $default = null)
    {
        $cacheEnabled = config('thinkrix.cache.settings.enabled', true);
        $cachePrefix = config('thinkrix.cache.settings.prefix', 'thinkrix.setting.');
        $cacheLifetime = config('thinkrix.cache.settings.ttl', 3600);

        if ($cacheEnabled) {
            return Cache::remember($cachePrefix . $key, function () use ($key, $default) {
                $setting = static::where('key', $key)->find();
                return $setting ? $setting->getTypedValue() : $default;
            }, $cacheLifetime);
        }

        $setting = static::where('key', $key)->find();
        return $setting ? $setting->getTypedValue() : $default;
    }

    /**
     * 设置值（不存在则创建）
     */
    public static function setValue(string $key, $value, ?string $group = null): bool
    {
        if (str_contains($key, '.') && $group === null) {
            [$group, $key] = explode('.', $key, 2);
        }

        $setting = static::where('key', $key)->find();

        if (!$setting) {
            $setting = new static([
                'group' => $group ?? 'general',
                'key' => $key,
                'type' => is_array($value) || is_object($value) ? 'json' : (is_bool($value) ? 'boolean' : 'string'),
            ]);
        }

        $setting->value = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
        $result = $setting->save();

        $cachePrefix = config('thinkrix.cache.settings.prefix', 'thinkrix.setting.');
        Cache::delete($cachePrefix . $key);

        return $result;
    }

    /**
     * 按分组获取设置（返回 key => value 格式）
     */
    public static function getGroup(string $group): array
    {
        $settings = static::where('group', $group)->select();

        $result = [];
        foreach ($settings as $setting) {
            $key = $setting->key;
            if (str_starts_with($key, $group . '.')) {
                $key = substr($key, strlen($group) + 1);
            }
            $result[$key] = $setting->getTypedValue();
        }

        return $result;
    }

    /**
     * 按分组获取设置（返回完整信息）
     */
    public static function getByGroup(string $group): array
    {
        $settings = static::where('group', $group)->order('sort')->select();

        $result = [];
        foreach ($settings as $setting) {
            $result[] = [
                'key' => $setting->key,
                'title' => $setting->title,
                'type' => $setting->getData('type'),
                'value' => $setting->getTypedValue(),
                'default_value' => $setting->getTypedDefaultValue(),
                'description' => $setting->description,
            ];
        }

        return $result;
    }

    /**
     * 获取类型化的值
     */
    public function getTypedValue()
    {
        return $this->castValue($this->value, $this->getData('type'));
    }

    /**
     * 获取类型化的默认值
     */
    public function getTypedDefaultValue()
    {
        return $this->castValue($this->default_value, $this->getData('type'));
    }

    /**
     * 根据类型转换值
     */
    protected function castValue($value, string $type)
    {
        if ($value === null) { return null; }
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}

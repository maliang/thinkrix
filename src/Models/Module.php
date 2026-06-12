<?php

namespace Thinkrix\Models;

use think\Model;

/**
 * Module - 模块模型
 *
 * @property int $id
 * @property string $name
 * @property string|null $title
 * @property string|null $description
 * @property string|null $version
 * @property string|null $author
 * @property string|null $website
 * @property string|null $logo
 * @property bool $enabled
 * @property array|null $config
 * @property string $created_at
 * @property string $updated_at
 */
class Module extends Model
{
    protected $table = 'modules';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'enabled' => 'boolean',
        'config' => 'array',
    ];

    protected $fillable = [
        'name', 'title', 'description', 'version', 'author', 'website', 'logo', 'enabled', 'config',
    ];

    /**
     * 查询启用的模块
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * 查询禁用的模块
     */
    public function scopeDisabled($query)
    {
        return $query->where('enabled', false);
    }

    /**
     * 检查模块是否启用
     */
    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    /**
     * 启用模块
     */
    public function enable(): bool
    {
        $this->enabled = true;
        return $this->save();
    }

    /**
     * 禁用模块
     */
    public function disable(): bool
    {
        $this->enabled = false;
        return $this->save();
    }
}

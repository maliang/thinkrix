<?php

namespace Thinkrix\Controllers;

use think\Request;
use think\facade\Cache;
use Thinkrix\Models\Setting;
use Thinkrix\Schema\Components\NaiveUI\Card;
use Thinkrix\Schema\Components\NaiveUI\Form;
use Thinkrix\Schema\Components\NaiveUI\FormItem;
use Thinkrix\Schema\Components\NaiveUI\Input;
use Thinkrix\Schema\Components\NaiveUI\SwitchC;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Space;

class SettingController extends Controller
{
    public function index(): array
    {
        $actionType = $this->input('action_type', 'list');
        return match ($actionType) {
            'form_ui' => $this->formUi(),
            default => $this->list(),
        };
    }

    protected function list(): array
    {
        $settings = Setting::order('group')->order('sort')->select();
        $groups = [];
        foreach ($settings as $setting) {
            $group = $setting->group;
            if (!isset($groups[$group])) { $groups[$group] = []; }
            $groups[$group][] = [
                'id' => $setting->id,
                'key' => $setting->key,
                'title' => $setting->title,
                'type' => $setting->type,
                'value' => $setting->getTypedValue(),
                'default_value' => $setting->getTypedDefaultValue(),
                'description' => $setting->description,
            ];
        }
        return success($groups);
    }

    public function group(string $group): array
    {
        return success(Setting::getByGroup($group));
    }

    public function update(): array
    {
        $data = request()->put();
        $this->validate($data, ['settings' => 'require|array']);

        $cachePrefix = config('thinkrix.cache.settings.prefix', 'thinkrix.setting.');

        foreach ($data['settings'] as $item) {
            if (empty($item['key'])) continue;
            $setting = Setting::where('key', $item['key'])->find();
            if ($setting) {
                $value = $item['value'];
                if (is_array($value) || is_object($value)) { $value = json_encode($value); }
                elseif (is_bool($value)) { $value = $value ? '1' : '0'; }
                else { $value = (string) $value; }
                $setting->value = $value;
                $setting->save();
                Cache::delete($cachePrefix . $item['key']);
            }
        }
        return success('更新成功');
    }

    protected function formUi(): array
    {
        $schema = Card::make()->title('系统设置')->children([
            Form::make()->props(['model' => '{{ formData }}', 'labelPlacement' => 'left', 'labelWidth' => 120])->children([
                FormItem::make()->label('系统名称')->children([Input::make()->model('formData.app_title')->placeholder('请输入系统名称')]),
                FormItem::make()->label('Logo 地址')->children([Input::make()->model('formData.logo')->placeholder('请输入 Logo 地址')]),
                FormItem::make()->label('版权信息')->children([Input::make()->model('formData.copyright')->placeholder('请输入版权信息')]),
                FormItem::make()->children([
                    Space::make()->children([
                        Button::make()->type('primary')->children(['保存设置'])->on('click', [
                            'fetch' => '/settings', 'method' => 'PUT',
                            'body' => ['settings' => [
                                ['key' => 'app_title', 'value' => '{{ formData.app_title }}'],
                                ['key' => 'logo', 'value' => '{{ formData.logo }}'],
                                ['key' => 'copyright', 'value' => '{{ formData.copyright }}'],
                            ]],
                            'then' => [['call' => '$message.success', 'args' => ['保存成功']]],
                        ]),
                    ]),
                ]),
            ]),
        ])->toArray();

        $schema['data'] = [
            'formData' => [
                'app_title' => config('thinkrix.app_title', 'Thinkrix Admin'),
                'logo' => config('thinkrix.logo', '/admin/favicon.svg'),
                'copyright' => config('thinkrix.copyright', '© ' . date('Y') . ' Thinkrix Admin. All rights reserved.'),
            ],
        ];

        return success($schema);
    }
}

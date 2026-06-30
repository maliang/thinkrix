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
use Thinkrix\Schema\Components\NaiveUI\Upload;
use Thinkrix\Schema\Components\NaiveUI\Image;
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
        $themeUpdates = [];
        $themeMapping = [
            'appTitle' => 'appTitle',
            'logo' => 'logo',
            'copyright' => 'copyright',
        ];

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

            if (array_key_exists($item['key'], $themeMapping)) {
                $themeUpdates[$themeMapping[$item['key']]] = $item['value'] ?? '';
            }
        }

        $this->syncThemeSettings($themeUpdates);

        return success(__t('crud.updated'));
    }

    protected function syncThemeSettings(array $themeUpdates): void
    {
        if ($themeUpdates === []) {
            return;
        }

        $theme = Setting::fetchValue('theme', config('thinkrix.theme', []));
        if (!is_array($theme)) {
            $theme = config('thinkrix.theme', []);
        }

        Setting::setValue('theme', array_merge($theme, $themeUpdates));
    }

    protected function formUi(): array
    {
        $uploadAction = '/' . trim((string) config('thinkrix.api_prefix', 'api/admin'), '/') . '/upload/image';

        $schema = Card::make()->title(__t('system.setting.title'))->children([
            Form::make()->props(['model' => '{{ formData }}', 'labelPlacement' => 'left', 'labelWidth' => 120])->children([
                FormItem::make()->label(__t('system.setting.form.appTitle'))->children([Input::make()->model('formData.appTitle')->placeholder(__t('system.setting.placeholder.appTitle'))]),
                FormItem::make()->label(__t('system.setting.form.logo_url'))->children([
                    Space::make()->props(['vertical' => true, 'size' => 'small'])->children([
                        Upload::make()
                            ->action($uploadAction)
                            ->accept('.jpg,.jpeg,.png,.gif,.webp,.ico')
                            // 不用 image-card + max，避免达到上限后触发器被隐藏（导致必须先删除才能再传）；
                            // 关闭自带文件列表，改用下方 NImage 作为可点击的上传触发区，点击当前 logo 即可重新上传。
                            ->showFileList(false)
                            ->props(['name' => 'file'])
                            ->on('finish', [
                                // Naive UI 的 onFinish 回调中 file 对象没有 response 字段，
                                // 上传返回数据只能从 XHR 事件读取：$event.event.target.response（原始 JSON 字符串），需 JSON.parse 解析。
                                ['set' => 'formData.logo', 'value' => '{{ JSON.parse($event.event.target.response)?.data?.url || "" }}'],
                                ['call' => '$methods.$message.success', 'args' => [__t('upload.ok')]],
                            ])
                            ->on('error', [
                                ['call' => '$methods.$message.error', 'args' => [__t('upload.failed')]],
                            ])
                            ->children([
                                // 已有 logo：直接点击图片即可重新选图上传（previewDisabled 确保点击冒泡到上传触发器，而非打开预览）
                                Image::make()
                                    ->if('formData.logo')
                                    ->src('{{ formData.logo }}')
                                    ->width(100)
                                    ->height(100)
                                    ->objectFit('contain')
                                    ->previewDisabled()
                                    ->props(['style' => 'cursor: pointer; display: block; border: 1px dashed #d9d9d9; border-radius: 6px; padding: 4px;']),
                                // 无 logo：显示选择按钮
                                Button::make()->if('!formData.logo')->children([__t('upload.select_image')]),
                            ]),
                    ]),
                ]),
                FormItem::make()->label(__t('system.setting.form.copyright'))->children([Input::make()->model('formData.copyright')->placeholder(__t('system.setting.placeholder.copyright'))]),
                FormItem::make()->children([
                    Space::make()->children([
                        Button::make()->type('primary')->children([__t('system.button.save')])->on('click', [
                            'fetch' => '/settings', 'method' => 'PUT',
                            'body' => ['settings' => [
                                ['key' => 'appTitle', 'value' => '{{ formData.appTitle }}'],
                                ['key' => 'logo', 'value' => '{{ formData.logo }}'],
                                ['key' => 'copyright', 'value' => '{{ formData.copyright }}'],
                            ]],
                            'then' => [
                                ['call' => '$methods.$theme.updateSite', 'args' => ['{{ formData.appTitle }}', '{{ formData.logo }}']],
                                ['call' => '$methods.$message.success', 'args' => [__t('system.message.config_saved')]],
                            ],
                        ]),
                    ]),
                ]),
            ]),
        ])->toArray();

        $theme = Setting::fetchValue('theme', config('thinkrix.theme', []));
        $logo = $theme['logo'] ?? '';
        $schema['data'] = [
            'formData' => [
                'appTitle' => $theme['appTitle'] ?? 'Thinkrix Admin',
                'logo' => $logo,
                'copyright' => config('thinkrix.copyright', '© ' . date('Y') . ' Thinkrix Admin. All rights reserved.'),
            ],
        ];

        return success($schema);
    }
}

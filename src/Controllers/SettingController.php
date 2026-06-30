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
                            ->max(1)
                            ->listType('image-card')
                            ->fileList('formData.logoFileList')
                            ->props(['name' => 'file'])
                            ->on('finish', [
                                ['set' => 'formData.logo', 'value' => '{{ $event.file.response?.data?.url || $event.file.response?.url || "" }}'],
                                ['set' => 'formData.logoFileList', 'value' => '{{ ($event.file.response?.data?.url || $event.file.response?.url) ? [{ id: $event.file.id || $event.file.name || "logo", name: $event.file.name || "logo", status: "finished", url: ($event.file.response?.data?.url || $event.file.response?.url) }] : [] }}'],
                                ['call' => '$methods.$message.success', 'args' => [__t('upload.ok')]],
                            ])
                            ->on('remove', [
                                ['set' => 'formData.logo', 'value' => ''],
                                ['set' => 'formData.logoFileList', 'value' => []],
                            ])
                            ->on('error', [
                                ['call' => '$methods.$message.error', 'args' => [__t('upload.failed')]],
                            ])
                            ->children([
                                Button::make()->children([__t('upload.select_image')]),
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

        $theme = \Thinkrix\Models\Setting::fetchThemeConfig(config('thinkrix.theme', []));
        $logo = $theme['logo'] ?? '';
        $schema['data'] = [
            'formData' => [
                'appTitle' => $theme['appTitle'] ?? 'Thinkrix Admin',
                'logo' => $logo,
                'logoFileList' => $logo !== '' ? [[
                    'id' => $logo,
                    'name' => basename(parse_url($logo, PHP_URL_PATH) ?: $logo),
                    'status' => 'finished',
                    'url' => $logo,
                ]] : [],
                'copyright' => config('thinkrix.copyright', '© ' . date('Y') . ' Thinkrix Admin. All rights reserved.'),
            ],
        ];

        return success($schema);
    }
}

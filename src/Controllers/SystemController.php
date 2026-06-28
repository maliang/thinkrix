<?php

namespace Thinkrix\Controllers;

use think\Request;
use think\Response;
use Thinkrix\Schema\Components\Custom\Html;
use Thinkrix\Schema\Components\NaiveUI\Card;
use Thinkrix\Schema\Components\NaiveUI\Flex;
use Thinkrix\Schema\Components\NaiveUI\Form;
use Thinkrix\Schema\Components\NaiveUI\FormItem;
use Thinkrix\Schema\Components\NaiveUI\Input;
use Thinkrix\Schema\Components\NaiveUI\InputGroup;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Checkbox;
use Thinkrix\Schema\Components\NaiveUI\Text;
use Thinkrix\Schema\Components\NaiveUI\Result;
use Thinkrix\Schema\Components\Custom\Icon;
use Thinkrix\Schema\Components\Custom\SvgIcon;
use Thinkrix\Schema\Components\Common\GlobalSearch;
use Thinkrix\Schema\Components\Common\FullScreen;
use Thinkrix\Schema\Components\Common\LangSwitch;
use Thinkrix\Schema\Components\Common\ThemeSchemaSwitch;
use Thinkrix\Schema\Components\Common\ThemeButton;
use Thinkrix\Schema\Components\Common\UserAvatar;
use Thinkrix\Schema\Components\Common\HeaderNotification;
use Thinkrix\Schema\Components\Common\HeaderCustomItem;
use Thinkrix\Services\TranslationService;

class SystemController extends Controller
{
    /**
     * 前端入口
     */
    public function entry(): Response
    {
        $rootPath = app()->getRootPath();
        $indexPath = $rootPath . 'public' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'index.html';

        if (!file_exists($indexPath)) {
            return json(['code' => 404, 'msg' => __t('system.entry.assets_not_publish')], 404);
        }

        $html = file_get_contents($indexPath);
        $config = $this->getEntryConfig();
        $script = '<script>window.__LARTRIX_CONFIG__ = ' . json_encode($config, JSON_UNESCAPED_UNICODE) . ';</script>';
        $html = str_replace('<head>', '<head>' . "\n    " . $script, $html);

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    protected function getEntryConfig(): array
    {
        $theme = $this->getThemeSettings();
        return [
            'apiPrefix' => '/' . ltrim(config('thinkrix.api_prefix', 'api/admin'), '/'),
            'appTitle' => $theme['appTitle'] ?? 'Thinkrix Admin',
            'logo' => $theme['logo'] ?? '',
            'locale' => config('thinkrix.locale', 'zh-CN'),
            'fallbackLocale' => config('thinkrix.fallback_locale', 'en-US'),
            'languages' => app(TranslationService::class)->getLanguageOptions(),
            'translationsUrl' => '/translations',
            'realtime' => $this->getRealtimeConfig(),
        ];
    }

    protected function getRealtimeConfig(): array
    {
        return [
            'enabled' => (bool) config('thinkrix.realtime.enabled', true),
            'enableNotification' => (bool) config('thinkrix.realtime.enable_notification', true),
            'driver' => config('thinkrix.realtime.driver', 'polling'),
            'polling' => [
                'api' => config('thinkrix.realtime.polling.api', '/notifications/poll'),
                'interval' => (int) config('thinkrix.realtime.polling.interval', 15000),
            ],
            'websocket' => [
                'url' => config('thinkrix.realtime.websocket.url', ''),
            ],
            'behaviors' => config('thinkrix.realtime.behaviors', []),
        ];
    }

    protected function getSettingModel(): string
    {
        return config('thinkrix.models.setting', \Thinkrix\Models\Setting::class);
    }

    /**
     * 获取完整主题配置（DB + 默认值合并）
     */
    protected function getThemeSettings(): array
    {
        $settingModel = $this->getSettingModel();
        return $settingModel::fetchThemeConfig($this->getDefaultThemeConfig());
    }

    public function loginPage(): array
    {
        $theme = $this->getThemeSettings();
        $appTitle = $theme['appTitle'] ?? 'Thinkrix Admin';
        $appSubtitle = 'JSON 驱动的后台管理系统';
        $copyright = $theme['copyright'] ?? config('thinkrix.copyright', 'Thinkrix Admin');
        $logo = $theme['logo'] ?? '';

        $schema = Html::div()
            ->data($this->getLoginPageData())
            ->props(['style' => ['minHeight' => '100vh', 'display' => 'flex', 'flexDirection' => 'column', 'justifyContent' => 'center', 'alignItems' => 'center', 'position' => 'relative', 'overflow' => 'hidden', 'background' => '#f8f9fc']])
            ->children([
                Html::make('img')->props(['src' => $logo, 'style' => ['width' => '48px', 'marginBottom' => '24px', 'zIndex' => 10]]),
                Card::make()->bordered(false)->props(['style' => ['width' => '400px', 'borderRadius' => '20px', 'boxShadow' => '0 25px 50px -12px rgba(0,0,0,0.25)', 'zIndex' => 10], 'contentStyle' => ['padding' => '40px']])
                    ->children([
                        Flex::make()->align('center')->justify('center')->props(['style' => ['marginBottom' => '32px']])->children([
                            Text::make()->strong()->props(['style' => ['fontSize' => '24px']])->children([$appTitle]),
                        ]),
                        Html::div()->if("mode === 'login'")->children([
                            Form::make()->model('form')->rules('rules')->showLabel(false)->children([
                                FormItem::make()->path('username')->children([Input::make()->model('form.username')->placeholder(__t('system.login.form.username'))->size('large')->clearable()]),
                                FormItem::make()->path('password')->children([Input::make()->model('form.password')->type('password')->placeholder(__t('system.login.form.password'))->size('large')->showPasswordOn('click')->clearable()]),
                                Button::make()->type('primary')->props(['block' => true, 'size' => 'large', 'loading' => '{{ loading }}', 'style' => ['height' => '44px']])
                                    ->on('click', ['script' => 'state.loading = true; try { await $methods.login(state.form.username, state.form.password); } finally { state.loading = false; }'])->text(__t('system.login.title')),
                            ]),
                        ]),
                    ]),
                Text::make()->props(['style' => ['marginTop' => '32px', 'color' => 'rgba(100,100,100,0.8)', 'fontSize' => '13px', 'zIndex' => 10]])->children([$copyright]),
            ]);

        return success($schema->toArray());
    }

    protected function getLoginPageData(): array
    {
        return [
            'mode' => 'login',
            'form' => ['username' => '', 'password' => ''],
            'loading' => false,
            'rememberMe' => false,
            'rules' => [
                'username' => [['required' => true, 'message' => __t('user.placeholder.username'), 'trigger' => 'blur']],
                'password' => [['required' => true, 'message' => __t('system.login.placeholder.password'), 'trigger' => 'blur'], ['min' => 6, 'message' => __t('system.login.message.password_min'), 'trigger' => 'blur']],
            ],
        ];
    }

    public function forbidden(): array
    {
        $schema = Flex::make()->vertical()->justify('center')->align('center')->props(['class' => 'min-h-screen'])->children([
            Result::make()->status('403')->title('403')->description(__t('system.error.403_desc'))
                ->slot('footer', [Flex::make()->justify('center')->props(['class' => 'gap-4'])->children([
                    Button::make()->type('primary')->on('click', ['call' => '$router.push', 'args' => ['/']])->text(__t('system.button.back_home')),
                    Button::make()->on('click', ['call' => '$router.back'])->text(__t('system.button.back_prev')),
                ])]),
        ]);
        return success($schema->toArray());
    }

    public function notFound(): array
    {
        $schema = Flex::make()->vertical()->justify('center')->align('center')->props(['class' => 'min-h-screen'])->children([
            Result::make()->status('404')->title('404')->description(__t('system.error.404_desc'))
                ->slot('footer', [Flex::make()->justify('center')->props(['class' => 'gap-4'])->children([
                    Button::make()->type('primary')->on('click', ['call' => '$router.push', 'args' => ['/']])->text(__t('system.button.back_home')),
                    Button::make()->on('click', ['call' => '$router.back'])->text(__t('system.button.back_prev')),
                ])]),
        ]);
        return success($schema->toArray());
    }

    public function headerRight(): array
    {
        // 自定义导航项位置：left（默认，最左）/ right（最右）
        $customItemsPosition = config('thinkrix.header.custom_items_position', 'left');
        $customItems = $this->buildHeaderCustomItems();

        $children = [];

        // 自定义项置于默认右侧组件整体的最左侧
        if ($customItemsPosition === 'left') {
            $children = array_merge($children, $customItems);
        }

        if (config('thinkrix.header.global_search', true)) {
            $children[] = GlobalSearch::make();
        }
        if (config('thinkrix.header.notification', true)) {
            $children[] = HeaderNotification::make()
                ->fetchApi('/notifications')
                ->readApi('/notifications/{id}/mark-read')
                ->readAllApi('/notifications/mark-all-read');
        }

        if (config('thinkrix.header.full_screen', true)) {
            $children[] = FullScreen::make();
        }
        if (config('thinkrix.header.lang_switch', true)) {
            $locale = $this->getUser()?->locale ?: config('thinkrix.locale', 'zh-CN');
            $translationService = app(TranslationService::class);
            $children[] = LangSwitch::make()->props([
                'langOptions' => $translationService->getLanguageOptions(),
                'defaultLang' => $locale,
                'submitUrl' => '/locale',
                'translationsUrl' => '/translations',
                'reloadOnChange' => true,
            ]);
        }
        if (config('thinkrix.header.theme_schema_switch', true)) {
            $children[] = ThemeSchemaSwitch::make();
        }
        if (config('thinkrix.header.theme_button', true)) {
            $children[] = ThemeButton::make();
        }

        $children[] = UserAvatar::make()->menuItems([
            ['key' => 'profile', 'label' => __t('system.avatar.profile'), 'icon' => 'ph:user', 'action' => 'modal', 'modal' => ['title' => __t('system.avatar.profile'), 'width' => 600, 'uiApi' => '/user/profile/ui']],
            ['key' => 'settings', 'label' => __t('system.avatar.settings'), 'icon' => 'ph:gear', 'action' => 'modal', 'modal' => ['title' => __t('system.avatar.settings'), 'width' => 500, 'uiApi' => '/user/settings/ui', 'submitApi' => '/user/settings']],
            ['key' => 'password', 'label' => __t('system.avatar.password'), 'icon' => 'ph:lock-key', 'action' => 'modal', 'modal' => ['title' => __t('system.avatar.password'), 'width' => 400, 'uiApi' => '/user/password/ui', 'submitApi' => '/user/password']],
            ['key' => 'divider1', 'divider' => true],
            ['key' => 'logout', 'label' => __t('system.avatar.logout'), 'icon' => 'ph:sign-out', 'action' => 'logout'],
        ]);

        // 自定义项置于默认右侧组件整体的最右侧
        if ($customItemsPosition !== 'left') {
            $children = array_merge($children, $customItems);
        }

        $schema = Html::div()->props(['class' => 'h-full flex-y-center gap-4px'])->children($children);
        return success($schema->toArray());
    }

    /**
     * 构建头部自定义导航项（从配置读取）
     */
    protected function buildHeaderCustomItems(): array
    {
        $items = [];
        foreach (config('thinkrix.header.custom_items', []) as $item) {
            $custom = HeaderCustomItem::make()
                ->icon($item['icon'] ?? 'carbon:dot-mark')
                ->tooltip($item['tooltip'] ?? '');
            if (!empty($item['badge']) && is_array($item['badge'])) {
                $custom->badge($item['badge']);
            }
            if (!empty($item['click'])) {
                $custom->click($item['click']);
            }
            if (!empty($item['click_target'])) {
                $custom->clickTarget($item['click_target']);
            }
            if (!empty($item['target'])) {
                $custom->target($item['target']);
            }
            if (!empty($item['schema_api'])) {
                $custom->schemaApi($item['schema_api']);
            }
            $items[] = $custom;
        }
        return $items;
    }

    public function getThemeConfig(): array
    {
        return success($this->getThemeSettings());
    }

    public function saveThemeConfig(): array
    {
        $data = request()->post();
        $settingModel = $this->getSettingModel();
        $settingModel::setValue('theme', $data);
        return success(__t('system.config_saved'));
    }

    public function getDefaultThemeConfig(): array
    {
        return config('thinkrix.theme', []);
    }

    /**
     * 获取翻译
     * GET /api/admin/system/translations?locale=zh-cn
     */
    public function translations(): array
    {
        $locale = $this->input('locale', config('thinkrix.locale', 'zh-CN'));
        $service = app(TranslationService::class);
        $locale = $service->normalizeLocale($locale);
        if ($locale === null) error(__t('system.message.locale_invalid'), null, 40022);
        return success($service->getTranslations($locale));
    }

    /**
     * 设置用户语言偏好
     * POST /api/admin/system/locale
     */
    public function setLocale(): array
    {
        $locale = $this->input('locale', 'zh-CN');
        $service = app(TranslationService::class);
        $locale = $service->normalizeLocale($locale);
        if ($locale === null) {
            error(__t('system.message.locale_invalid'), null, 40022);
        }
        $user = $this->getUser();
        if ($user) {
            $user->locale = $locale;
            $user->save();
        }
        app()->lang->setLangSet(strtolower(str_replace('_', '-', $locale)));
        return success(__t('system.message.locale_saved'));
    }
}

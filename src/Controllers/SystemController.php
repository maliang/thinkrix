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

    protected function getNotificationTabs(): array
    {
        return [
            ['key' => 'all', 'label' => __t('role.filter_all'), 'icon' => 'ph:bell', 'types' => []],
            ['key' => 'system', 'label' => __t('notification.type_system'), 'icon' => 'ph:gear', 'types' => ['system']],
            ['key' => 'notice', 'label' => __t('notification.type_notice'), 'icon' => 'ph:bell', 'types' => ['notice']],
            ['key' => 'message', 'label' => __t('notification.type_message'), 'icon' => 'ph:chat-circle', 'types' => ['message']],
            ['key' => 'todo', 'label' => __t('notification.type_todo'), 'icon' => 'ph:check-square', 'types' => ['todo']],
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
        $appSubtitle = $theme['appSubtitle'] ?? config('thinkrix.theme.appSubtitle', __t('system.login.subtitle'));
        $copyright = $theme['copyright'] ?? config('thinkrix.copyright', 'Thinkrix Admin');
        $logo = $theme['logo'] ?? '';

        $schema = Html::div()
            ->data($this->getLoginPageData())
            ->props(['style' => ['minHeight' => '100vh', 'display' => 'flex', 'flexDirection' => 'column', 'justifyContent' => 'center', 'alignItems' => 'center', 'position' => 'relative', 'overflow' => 'hidden', 'background' => '#f8f9fc']])
            ->children([
                // 动画 SVG 背景
                $this->buildAnimatedSvgBackground(),
                // 波浪动画样式
                $this->buildWaveStyles(),
                // 波浪容器
                $this->buildWaveContainer(),
                // 顶部渐变动画
                $this->buildTopGradient(),
                // 登录卡片
                $this->buildLoginCard($appTitle, $appSubtitle, $logo),
                // 版权信息
                Text::make()
                    ->props(['style' => ['marginTop' => '32px', 'color' => 'rgba(100,100,100,0.8)', 'fontSize' => '13px', 'zIndex' => '10']])
                    ->children([$copyright]),
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
                'username' => [['required' => true, 'message' => __t('system.login.message.username_required'), 'trigger' => 'blur']],
                'password' => [['required' => true, 'message' => __t('system.login.message.password_required'), 'trigger' => 'blur'], ['min' => 6, 'message' => __t('system.login.message.password_min'), 'trigger' => 'blur']],
            ],
        ];
    }

    /**
     * 动画 SVG 背景
     */
    protected function buildAnimatedSvgBackground(): Html
    {
        $svg = "<svg viewBox='0 0 1000 600' preserveAspectRatio='xMidYMid slice' style='position:absolute;top:0;left:0;width:100%;height:60%;pointer-events:none;filter:saturate(0.5);'>"
            . "<defs>"
            . "<linearGradient id='lg1' x1='0%' y1='0%' x2='100%' y2='100%'><stop offset='0%' style='stop-color:rgb(var(--primary-400-color));stop-opacity:0.2'/><stop offset='100%' style='stop-color:rgb(var(--primary-300-color));stop-opacity:0.1'/></linearGradient>"
            . "<linearGradient id='lg2' x1='100%' y1='0%' x2='0%' y2='100%'><stop offset='0%' style='stop-color:rgb(var(--primary-300-color));stop-opacity:0.15'/><stop offset='100%' style='stop-color:rgb(var(--primary-200-color));stop-opacity:0.08'/></linearGradient>"
            . "<linearGradient id='lg3' x1='0%' y1='100%' x2='100%' y2='0%'><stop offset='0%' style='stop-color:rgb(var(--primary-500-color));stop-opacity:0.12'/><stop offset='100%' style='stop-color:rgb(var(--primary-400-color));stop-opacity:0.06'/></linearGradient>"
            . "</defs>"
            . "<path stroke='url(#lg1)' stroke-width='1.5' fill='none'><animate attributeName='d' dur='20s' repeatCount='indefinite' values='M0,150 Q200,100 400,180 T800,120 T1000,160;M0,120 Q200,180 400,100 T800,180 T1000,130;M0,180 Q200,120 400,160 T800,100 T1000,150;M0,150 Q200,100 400,180 T800,120 T1000,160'/></path>"
            . "<path stroke='url(#lg2)' stroke-width='1' fill='none'><animate attributeName='d' dur='25s' repeatCount='indefinite' values='M0,250 Q250,200 500,280 T1000,220;M0,220 Q250,280 500,200 T1000,260;M0,280 Q250,220 500,260 T1000,200;M0,250 Q250,200 500,280 T1000,220'/></path>"
            . "<path stroke='url(#lg1)' stroke-width='0.8' fill='none' opacity='0.6'><animate attributeName='d' dur='18s' repeatCount='indefinite' values='M0,80 Q300,120 600,60 T1000,100;M0,100 Q300,60 600,120 T1000,70;M0,60 Q300,100 600,80 T1000,110;M0,80 Q300,120 600,60 T1000,100'/></path>"
            . "<path stroke='url(#lg3)' stroke-width='1.2' fill='none'><animate attributeName='d' dur='22s' repeatCount='indefinite' values='M0,320 Q180,280 360,340 T720,300 T1000,330;M0,300 Q180,340 360,280 T720,340 T1000,290;M0,340 Q180,300 360,320 T720,280 T1000,320;M0,320 Q180,280 360,340 T720,300 T1000,330'/></path>"
            . "<circle r='3' style='fill:rgb(var(--primary-400-color));fill-opacity:0.25'><animate attributeName='cx' dur='15s' repeatCount='indefinite' values='100;300;100'/><animate attributeName='cy' dur='12s' repeatCount='indefinite' values='150;200;150'/></circle>"
            . "<circle r='2' style='fill:rgb(var(--primary-300-color));fill-opacity:0.3'><animate attributeName='cx' dur='18s' repeatCount='indefinite' values='700;500;700'/><animate attributeName='cy' dur='14s' repeatCount='indefinite' values='100;180;100'/></circle>"
            . "<circle r='3.5' style='fill:rgb(var(--primary-500-color));fill-opacity:0.2'><animate attributeName='cx' dur='22s' repeatCount='indefinite' values='400;600;400'/><animate attributeName='cy' dur='16s' repeatCount='indefinite' values='250;180;250'/></circle>"
            . "</svg>";

        return Html::div()
            ->innerHTML($svg)
            ->css(['position' => 'absolute', 'inset' => '0', 'pointerEvents' => 'none']);
    }

    /**
     * 波浪动画样式
     */
    protected function buildWaveStyles(): Html
    {
        $css = ".wave-container { position: absolute; bottom: 0; left: 0; width: 100%; height: 85%; overflow: hidden; pointer-events: none; filter: saturate(0.6); } "
            . ".wave { position: absolute; left: 0; width: 200%; display: flex; animation: waveAnim 10s linear infinite; } "
            . ".wave svg { flex: 0 0 50%; height: 100%; display: block; } "
            . ".wave1 { animation-duration: 25s; bottom: 0; height: 100%; } "
            . ".wave2 { animation-duration: 20s; bottom: 0; height: 75%; } "
            . ".wave3 { animation-duration: 15s; bottom: 0; height: 50%; } "
            . ".wave4 { animation-duration: 10s; bottom: 0; height: 30%; } "
            . "@keyframes waveAnim { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }";

        return Html::style($css);
    }

    /**
     * 波浪容器
     */
    protected function buildWaveContainer(): Html
    {
        $waveSvg1 = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 200' preserveAspectRatio='none'><path d='M0,100 C200,110 400,90 600,100 C800,110 1000,90 1200,100 L1200,200 L0,200 Z' style='fill:rgb(var(--primary-200-color))'/></svg>";
        $waveSvg2 = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 200' preserveAspectRatio='none'><path d='M0,100 C200,130 400,70 600,100 C800,130 1000,70 1200,100 L1200,200 L0,200 Z' style='fill:rgb(var(--primary-300-color))'/></svg>";
        $waveSvg3 = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 200' preserveAspectRatio='none'><path d='M0,100 C200,160 400,40 600,100 C800,160 1000,40 1200,100 L1200,200 L0,200 Z' style='fill:rgb(var(--primary-400-color))'/></svg>";
        $waveSvg4 = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 200' preserveAspectRatio='none'><path d='M0,100 C200,195 400,5 600,100 C800,195 1000,5 1200,100 L1200,200 L0,200 Z' style='fill:rgb(var(--primary-500-color))'/></svg>";

        return Html::div()
            ->class('wave-container')
            ->children([
                Html::div()->class('wave wave1')->innerHTML($waveSvg1 . $waveSvg1),
                Html::div()->class('wave wave2')->innerHTML($waveSvg2 . $waveSvg2),
                Html::div()->class('wave wave3')->innerHTML($waveSvg3 . $waveSvg3),
                Html::div()->class('wave wave4')->innerHTML($waveSvg4 . $waveSvg4),
            ]);
    }

    /**
     * 顶部渐变动画
     */
    protected function buildTopGradient(): Html
    {
        $svg = "<svg viewBox='0 0 1000 300' preserveAspectRatio='none' style='position:absolute;top:0;left:0;width:100%;height:40%;transform:rotate(180deg)'>"
            . "<defs><linearGradient id='tg1' x1='0%' y1='0%' x2='100%' y2='0%'>"
            . "<stop offset='0%'><animate attributeName='stop-color' values='rgba(255,255,255,0.3);rgba(255,255,255,0.1);rgba(255,255,255,0.2);rgba(255,255,255,0.3)' dur='10s' repeatCount='indefinite'/></stop>"
            . "<stop offset='100%'><animate attributeName='stop-color' values='rgba(255,255,255,0.1);rgba(255,255,255,0.25);rgba(255,255,255,0.15);rgba(255,255,255,0.1)' dur='10s' repeatCount='indefinite'/></stop>"
            . "</linearGradient></defs>"
            . "<path fill='url(#tg1)'><animate attributeName='d' dur='14s' repeatCount='indefinite' values='M0,100 Q250,50 500,100 T1000,100 L1000,300 L0,300 Z;M0,80 Q250,130 500,80 T1000,80 L1000,300 L0,300 Z;M0,100 Q250,50 500,100 T1000,100 L1000,300 L0,300 Z'/></path>"
            . "</svg>";

        return Html::div()
            ->innerHTML($svg)
            ->css(['position' => 'absolute', 'inset' => '0', 'pointerEvents' => 'none']);
    }

    /**
     * 登录卡片
     */
    protected function buildLoginCard(string $appTitle, string $appSubtitle, string $logo): Card
    {
        return Card::make()
            ->bordered(false)
            ->props([
                'style' => [
                    'width' => '400px',
                    'borderRadius' => '20px',
                    'boxShadow' => '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
                    'background' => 'rgba(255, 255, 255, 0.95)',
                    'backdropFilter' => 'blur(20px)',
                    'WebkitBackdropFilter' => 'blur(20px)',
                    'zIndex' => '10',
                ],
                'contentStyle' => ['padding' => '40px'],
            ])
            ->children([
                $this->buildLogoHeader($appTitle, $appSubtitle, $logo),
                $this->buildLoginForm(),
            ]);
    }

    /**
     * Logo 头部
     */
    protected function buildLogoHeader(string $appTitle, string $appSubtitle, string $logo): Flex
    {
        return Flex::make()
            ->align('center')
            ->justify('center')
            ->props(['style' => ['marginBottom' => '32px', 'gap' => '12px']])
            ->children([
                Html::make('img')->props(['src' => $logo, 'style' => ['height' => '48px', 'width' => 'auto']]),
                Flex::make()->vertical()->props(['style' => ['gap' => '2px']])->children([
                    Text::make()->strong()->props(['style' => ['fontSize' => '24px', 'lineHeight' => '1.2']])->children([$appTitle]),
                    Text::make()->depth(3)->props(['style' => ['fontSize' => '12px']])->children([$appSubtitle]),
                ]),
            ]);
    }

    /**
     * 登录表单
     */
    protected function buildLoginForm(): Html
    {
        $loginScript = 'state.loading = true; try { await $methods.login(state.form.username, state.form.password); } finally { state.loading = false; }';

        return Html::div()
            ->if("mode === 'login'")
            ->children([
                Form::make()->model('form')->rules('rules')->showLabel(false)->children([
                    FormItem::make()->path('username')->children([
                        Input::make()->model('form.username')->placeholder(__t('system.login.placeholder.username'))->size('large')->clearable()
                            ->slot('prefix', [Icon::make('carbon:user')->props(['style' => ['color' => '#999']])]),
                    ]),
                    FormItem::make()->path('password')->children([
                        Input::make()->model('form.password')->type('password')->placeholder(__t('system.login.placeholder.password'))->size('large')->showPasswordOn('click')->clearable()
                            ->slot('prefix', [Icon::make('carbon:password')->props(['style' => ['color' => '#999']])]),
                    ]),
                    Flex::make()->align('center')->props(['style' => ['marginBottom' => '24px']])->children([
                        Checkbox::make()->props(['model:checked' => 'rememberMe'])->children([__t('system.login.remember_me')]),
                    ]),
                    Button::make()->type('primary')->props(['block' => true, 'size' => 'large', 'loading' => '{{ loading }}', 'attrType' => 'submit', 'style' => ['height' => '44px', 'fontSize' => '16px']])
                        ->on('click', ['script' => $loginScript])->text(__t('system.login.title')),
                ]),
            ]);
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
            $notification = HeaderNotification::make()
                ->fetchApi('/notifications')
                ->readApi('/notifications/{id}/mark-read')
                ->readAllApi('/notifications/mark-all-read')
                ->badgeMode('count')
                ->pageSize(10)
                ->enableNotification(true)
                ->notificationDuration(4500)
                ->enableDetail(true)
                ->tabs($this->getNotificationTabs())
                ->titlePrefixField('categoryLabel');

            $children[] = $notification;
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

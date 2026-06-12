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
            return json(['code' => 404, 'msg' => '前端资源未发布，请先发布资源'], 404);
        }

        $html = file_get_contents($indexPath);
        $config = $this->getEntryConfig();
        $script = '<script>window.__LARTRIX_CONFIG__ = ' . json_encode($config, JSON_UNESCAPED_UNICODE) . ';</script>';
        $html = str_replace('<head>', '<head>' . "\n    " . $script, $html);

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    protected function getEntryConfig(): array
    {
        return [
            'apiPrefix' => '/' . ltrim(config('thinkrix.api_prefix', 'api/admin'), '/'),
            'appTitle' => config('thinkrix.app_title', 'Thinkrix Admin'),
            'logo' => config('thinkrix.logo'),
        ];
    }

    protected function getSettingModel(): string
    {
        return config('thinkrix.models.setting', \Thinkrix\Models\Setting::class);
    }

    public function loginPage(): array
    {
        $appTitle = config('thinkrix.app_title', 'Thinkrix Admin');
        $appSubtitle = 'JSON 驱动的后台管理系统';
        $copyright = config('thinkrix.copyright', 'Thinkrix Admin');

        $schema = Html::div()
            ->data($this->getLoginPageData())
            ->props(['style' => ['minHeight' => '100vh', 'display' => 'flex', 'flexDirection' => 'column', 'justifyContent' => 'center', 'alignItems' => 'center', 'position' => 'relative', 'overflow' => 'hidden', 'background' => '#f8f9fc']])
            ->children([
                Html::make('img')->props(['src' => config('thinkrix.logo'), 'style' => ['width' => '48px', 'marginBottom' => '24px', 'zIndex' => 10]]),
                Card::make()->bordered(false)->props(['style' => ['width' => '400px', 'borderRadius' => '20px', 'boxShadow' => '0 25px 50px -12px rgba(0,0,0,0.25)', 'zIndex' => 10], 'contentStyle' => ['padding' => '40px']])
                    ->children([
                        Flex::make()->align('center')->justify('center')->props(['style' => ['marginBottom' => '32px']])->children([
                            Text::make()->strong()->props(['style' => ['fontSize' => '24px']])->children([$appTitle]),
                        ]),
                        Html::div()->if("mode === 'login'")->children([
                            Form::make()->model('form')->rules('rules')->showLabel(false)->children([
                                FormItem::make()->path('username')->children([Input::make()->model('form.username')->placeholder('用户名')->size('large')->clearable()]),
                                FormItem::make()->path('password')->children([Input::make()->model('form.password')->type('password')->placeholder('密码')->size('large')->showPasswordOn('click')->clearable()]),
                                Button::make()->type('primary')->props(['block' => true, 'size' => 'large', 'loading' => '{{ loading }}', 'style' => ['height' => '44px']])
                                    ->on('click', ['script' => 'state.loading = true; try { await $methods.login(state.form.username, state.form.password); } finally { state.loading = false; }'])->text('登 录'),
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
                'username' => [['required' => true, 'message' => '请输入用户名', 'trigger' => 'blur']],
                'password' => [['required' => true, 'message' => '请输入密码', 'trigger' => 'blur'], ['min' => 6, 'message' => '密码长度不能少于6位', 'trigger' => 'blur']],
            ],
        ];
    }

    public function forbidden(): array
    {
        $schema = Flex::make()->vertical()->justify('center')->align('center')->props(['class' => 'min-h-screen'])->children([
            Result::make()->status('403')->title('403')->description('抱歉，您没有权限访问此页面')
                ->slot('footer', [Flex::make()->justify('center')->props(['class' => 'gap-4'])->children([
                    Button::make()->type('primary')->on('click', ['call' => '$router.push', 'args' => ['/']])->text('返回首页'),
                    Button::make()->on('click', ['call' => '$router.back'])->text('返回上一页'),
                ])]),
        ]);
        return success($schema->toArray());
    }

    public function notFound(): array
    {
        $schema = Flex::make()->vertical()->justify('center')->align('center')->props(['class' => 'min-h-screen'])->children([
            Result::make()->status('404')->title('404')->description('抱歉，您访问的页面不存在')
                ->slot('footer', [Flex::make()->justify('center')->props(['class' => 'gap-4'])->children([
                    Button::make()->type('primary')->on('click', ['call' => '$router.push', 'args' => ['/']])->text('返回首页'),
                    Button::make()->on('click', ['call' => '$router.back'])->text('返回上一页'),
                ])]),
        ]);
        return success($schema->toArray());
    }

    public function headerRight(): array
    {
        $children = [];

        if (config('thinkrix.header.global_search', true)) {
            $children[] = GlobalSearch::make();
        }
        if (config('thinkrix.header.notification', true)) {
            $notification = HeaderNotification::make()
                ->fetchApi('/notifications')
                ->readApi('/notifications/{id}/mark-read')
                ->readAllApi('/notifications/mark-all-read');

            // 实时消息配置
            if (config('thinkrix.realtime.enabled', true)) {
                $driver = config('thinkrix.realtime.driver', 'polling');
                if ($driver === 'polling') {
                    $notification->enablePolling(true)
                        ->pollingInterval((int) config('thinkrix.realtime.polling.interval', 15000))
                        ->pollingApi(config('thinkrix.realtime.polling.api', '/notifications/poll'));
                } elseif ($driver === 'ws') {
                    $wsUrl = config('thinkrix.realtime.websocket.url', '');
                    if ($wsUrl) {
                        $notification->enableWs(true)->wsUrl($wsUrl);
                    }
                }
            }

            $children[] = $notification;
        }

        // 自定义导航项（从配置读取）
        foreach (config('thinkrix.header.custom_items', []) as $item) {
            $custom = HeaderCustomItem::make()
                ->icon($item['icon'] ?? 'carbon:dot-mark')
                ->tooltip($item['tooltip'] ?? '')
                ->badgeColor($item['badge_color'] ?? '');
            if (!empty($item['badge_api'])) {
                $custom->badgeApi($item['badge_api']);
            }
            if (!empty($item['click'])) {
                $custom->click($item['click']);
            }
            if (!empty($item['click_target'])) {
                $custom->clickTarget($item['click_target']);
            }
            if (!empty($item['schema_api'])) {
                $custom->schemaApi($item['schema_api']);
            }
            $children[] = $custom;
        }

        if (config('thinkrix.header.full_screen', true)) {
            $children[] = FullScreen::make();
        }
        if (config('thinkrix.header.lang_switch', true)) {
            $children[] = LangSwitch::make();
        }
        if (config('thinkrix.header.theme_schema_switch', true)) {
            $children[] = ThemeSchemaSwitch::make();
        }
        if (config('thinkrix.header.theme_button', true)) {
            $children[] = ThemeButton::make();
        }

        $children[] = UserAvatar::make()->menuItems([
            ['key' => 'profile', 'label' => '个人中心', 'icon' => 'ph:user', 'action' => 'modal', 'modal' => ['title' => '个人中心', 'width' => 600, 'uiApi' => '/user/profile/ui']],
            ['key' => 'settings', 'label' => '账号设置', 'icon' => 'ph:gear', 'action' => 'modal', 'modal' => ['title' => '账号设置', 'width' => 500, 'uiApi' => '/user/settings/ui', 'submitApi' => '/user/settings']],
            ['key' => 'password', 'label' => '修改密码', 'icon' => 'ph:lock-key', 'action' => 'modal', 'modal' => ['title' => '修改密码', 'width' => 400, 'uiApi' => '/user/password/ui', 'submitApi' => '/user/password']],
            ['key' => 'divider1', 'divider' => true],
            ['key' => 'logout', 'label' => '退出登录', 'icon' => 'ph:sign-out', 'action' => 'logout'],
        ]);

        $schema = Html::div()->props(['class' => 'h-full flex-y-center gap-4px'])->children($children);
        return success($schema->toArray());
    }

    public function getThemeConfig(): array
    {
        $settingModel = $this->getSettingModel();
        $themeConfig = $settingModel::getValue('theme', $this->getDefaultThemeConfig());
        return success($themeConfig);
    }

    public function saveThemeConfig(): array
    {
        $data = request()->post();
        $settingModel = $this->getSettingModel();
        $settingModel::setValue('theme', $data);
        return success('保存成功');
    }

    public function getDefaultThemeConfig(): array
    {
        return config('thinkrix.theme', []);
    }
}

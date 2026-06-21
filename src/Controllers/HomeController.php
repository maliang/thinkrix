<?php

namespace Thinkrix\Controllers;

use Thinkrix\Schema\Components\NaiveUI\Card;
use Thinkrix\Schema\Components\NaiveUI\Text;
use Thinkrix\Schema\Components\NaiveUI\Flex;
use Thinkrix\Schema\Components\NaiveUI\Statistic;
use Thinkrix\Schema\Components\NaiveUI\Tag;
use Thinkrix\Schema\Components\Custom\SvgIcon;
use Thinkrix\Schema\Components\Custom\Html;
use Thinkrix\Models\AdminUser;
use Thinkrix\Models\Role;

class HomeController extends Controller
{
    /**
     * 仪表盘数据
     */
    public function dashboard(): array
    {
        $userModel = config('thinkrix.models.user', AdminUser::class);
        $roleModel = config('thinkrix.models.role', Role::class);

        $userCount = $userModel::count();
        $activeUserCount = $userModel::where('status', '1')->count();
        $roleCount = $roleModel::where('guard_name', config('thinkrix.guard', 'admin'))->count();

        $schema = Html::div()->props(['class' => 'p-4'])->children([
            // 统计卡片
            Card::make()->bordered(false)->props(['title' => __t('system.dashboard.title'), 'style' => ['marginBottom' => '16px']])->children([
                Flex::make()->props(['gap' => 16])->children([
                    $this->buildStatCard(__t('system.dashboard.total_users'), $userCount, 'carbon:user-avatar', '#1890ff'),
                    $this->buildStatCard(__t('system.dashboard.active_users'), $activeUserCount, 'carbon:user-avatar-filled', '#52c41a'),
                    $this->buildStatCard(__t('system.dashboard.total_roles'), $roleCount, 'carbon:account', '#faad14'),
                ]),
            ]),
            // 欢迎信息
            Card::make()->bordered(false)->children([
                Flex::make()->vertical()->children([
                    Text::make()->strong()->props(['style' => ['fontSize' => '20px']])->children([__t('system.dashboard.welcome')]),
                    Text::make()->depth(3)->children([__t('system.dashboard.welcome_desc')]),
                ]),
            ]),
        ]);

        return success($schema->toArray());
    }

    protected function buildStatCard(string $label, $value, string $icon, string $color): Card
    {
        return Card::make()->bordered(false)->props(['style' => ['flex' => '1', 'backgroundColor' => $color . '10', 'borderLeft' => "4px solid {$color}"]])->children([
            Flex::make()->align('center')->props(['gap' => 16])->children([
                SvgIcon::make($icon)->props(['class' => 'text-3xl', 'style' => ['color' => $color]]),
                Flex::make()->vertical()->children([
                    Text::make()->depth(3)->props(['style' => ['fontSize' => '12px']])->children([$label]),
                    Text::make()->strong()->props(['style' => ['fontSize' => '24px', 'color' => $color]])->children(["{$value}"]),
                ]),
            ]),
        ]);
    }
}

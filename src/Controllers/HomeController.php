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
            Card::make()->bordered(false)->props(['title' => '仪表盘', 'style' => ['marginBottom' => '16px']])->children([
                Flex::make()->props(['gap' => 16])->children([
                    $this->buildStatCard('用户总数', $userCount, 'carbon:user-avatar', '#1890ff'),
                    $this->buildStatCard('活跃用户', $activeUserCount, 'carbon:user-avatar-filled', '#52c41a'),
                    $this->buildStatCard('角色数量', $roleCount, 'carbon:account', '#faad14'),
                ]),
            ]),
            // 欢迎信息
            Card::make()->bordered(false)->children([
                Flex::make()->vertical()->children([
                    Text::make()->strong()->props(['style' => ['fontSize' => '20px']])->children(['欢迎使用 Thinkrix 后台管理系统']),
                    Text::make()->depth(3)->children(['基于 ThinkPHP 8 和 Trix 前端的后台管理解决方案']),
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

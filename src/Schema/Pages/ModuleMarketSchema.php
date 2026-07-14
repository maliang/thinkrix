<?php

namespace Thinkrix\Schema\Pages;

use Thinkrix\Schema\Actions\SetAction;
use Thinkrix\Schema\Components\Custom\Html;
use Thinkrix\Schema\Components\Custom\SvgIcon;
use Thinkrix\Schema\Components\NaiveUI\Avatar;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\Components\NaiveUI\Card;
use Thinkrix\Schema\Components\NaiveUI\Flex;
use Thinkrix\Schema\Components\NaiveUI\Input;
use Thinkrix\Schema\Components\NaiveUI\Modal;
use Thinkrix\Schema\Components\NaiveUI\Pagination;
use Thinkrix\Schema\Components\NaiveUI\Result;
use Thinkrix\Schema\Components\NaiveUI\Select;
use Thinkrix\Schema\Components\NaiveUI\Space;
use Thinkrix\Schema\Components\NaiveUI\TabPane;
use Thinkrix\Schema\Components\NaiveUI\Tabs;
use Thinkrix\Schema\Components\NaiveUI\Tag;
use Thinkrix\Support\ModuleMarketTypes;

/** 构建独立模块市场页面、弹窗、卡片和分页。 */
final class ModuleMarketSchema
{
    public function __construct(private readonly ModuleMarketTypes $types) {}
    public function moduleTypeOptions(): array { return $this->types->moduleOptions(); }
    public function projectTypeOptions(): array { return $this->types->projectOptions(); }

    public function market(): array
    {
        $schema = Card::make()->props(['title' => __t('module.market.title')])->children([
            Result::make()->props(['status' => 'info', 'title' => '模块市场', 'description' => '请从模块管理页打开市场并安装模块或项目。'])
                ->slot('icon', [SvgIcon::make('carbon:store')->props(['class' => 'text-6xl text-primary'])]),
        ]);
        return success($schema->toArray());
    }

    public function modal(): Modal
    {
        return Modal::make()->props(['show' => '{{ marketVisible }}', 'title' => '模块市场', 'style' => ['width' => '1080px', 'height' => '760px'],
            'preset' => 'card', 'content-style' => ['height' => '682px', 'padding' => '16px 20px 12px', 'overflow' => 'hidden', 'boxSizing' => 'border-box']])
            ->on('update:show', SetAction::make('marketVisible', '{{ $event }}'))
            ->children([Tabs::make()->type('line')->model(['value' => 'marketActiveTab'])
                ->props(['style' => ['height' => '100%', 'display' => 'flex', 'flexDirection' => 'column']])->children([
                    TabPane::make()->name('modules')->tab('模块')->children($this->pane('module')),
                    TabPane::make()->name('projects')->tab('项目')->children($this->pane('project')),
                ])]);
    }

    /** 构建模块或项目 Tab。 */
    private function pane(string $kind): array
    {
        $project = $kind === 'project';
        $prefix = $project ? 'marketProject' : 'marketModule';
        return [Flex::make()->vertical()->props(['style' => ['height' => '626px', 'overflow' => 'hidden']])->children([
            Space::make()->props(['style' => 'margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #eef2f6;'])->children([
                Select::make()->model(['value' => $prefix . 'Type'])->props(['clearable' => false,
                    'options' => '{{ ' . $prefix . 'TypeOptions }}', 'style' => ['width' => '160px']]),
                Input::make()->model(['value' => $prefix . 'Keyword'])->props(['placeholder' => $project ? '搜索项目名称、ID 或描述' : '搜索模块名称、ID 或描述',
                    'clearable' => true, 'style' => ['width' => '280px']]),
                Button::make()->type('primary')->on('click', ['call' => $project ? 'searchMarketProjects' : 'searchMarketModules'])->text('搜索'),
            ]),
            $this->cards($project ? 'marketProjects' : 'marketModules', $kind),
            Flex::make()->props(['justify' => 'end', 'align' => 'center', 'style' => ['height' => '48px', 'flex' => '0 0 48px', 'paddingTop' => '10px', 'borderTop' => '1px solid #e5e7eb']])->children([
                Pagination::make()->props(['page' => '{{ ' . $prefix . 'Page }}', 'pageSize' => '{{ ' . $prefix . 'PageSize }}',
                    'itemCount' => '{{ ' . $prefix . 'Total }}', 'showSizePicker' => false])
                    ->on('update:page', ['call' => $project ? 'handleMarketProjectPageChange' : 'handleMarketModulePageChange', 'args' => ['{{ $event }}']]),
            ]),
        ])];
    }

    /** 构建固定四列市场卡片。 */
    private function cards(string $items, string $kind): Html
    {
        $project = $kind === 'project';
        return Html::div()->props(['style' => ['flex' => '1 1 0%', 'overflowY' => 'auto', 'display' => 'grid',
            'gridTemplateColumns' => 'repeat(4, minmax(0, 1fr))', 'gridAutoRows' => '136px', 'gap' => '10px', 'alignContent' => 'start']])->children([
            Card::make()->for("item in {$items}", 'item.id')->bordered(true)->size('small')
                ->props(['style' => ['height' => '136px', 'cursor' => 'pointer'], 'content-style' => ['height' => '100%', 'padding' => '10px 12px']])
                ->on('click', ['call' => $project ? 'showMarketProjectDetail' : 'showMarketModuleDetail', 'args' => ['{{ item }}']])->children([
                    Flex::make()->props(['align' => 'center', 'style' => ['gap' => '10px', 'marginBottom' => '7px']])->children([
                        Avatar::make()->if('item.logo')->props(['src' => '{{ item.logo }}', 'size' => 36, 'objectFit' => 'contain']),
                        SvgIcon::make($project ? 'carbon:template' : 'carbon:cube')->if('!item.logo')->props(['class' => 'text-2xl text-primary']),
                        Html::div()->props(['style' => ['minWidth' => 0, 'flex' => 1]])->children([
                            Html::div()->props(['style' => ['fontWeight' => 600, 'whiteSpace' => 'nowrap', 'overflow' => 'hidden', 'textOverflow' => 'ellipsis']])->children(['{{ item.title }}']),
                            Html::div()->props(['style' => ['fontSize' => '12px', 'color' => '#667085', 'whiteSpace' => 'nowrap', 'overflow' => 'hidden', 'textOverflow' => 'ellipsis']])->children(['{{ item.id }}']),
                        ]),
                    ]),
                    Html::div()->props(['style' => ['height' => '38px', 'fontSize' => '12px', 'lineHeight' => '19px', 'overflow' => 'hidden']])->children(['{{ item.summary || "暂无简介" }}']),
                    Flex::make()->props(['justify' => 'space-between', 'style' => ['marginTop' => '8px']])->children([
                        Tag::make()->props(['size' => 'small', 'bordered' => false])->children(['{{ item.type_label || item.type || "-" }}']),
                        Tag::make()->props(['size' => 'small', 'type' => '{{ item.installed ? "success" : "default" }}'])->children(['{{ item.installed ? "已安装" : item.version }}']),
                    ]),
                ]),
            Html::div()->if("!{$items} || {$items}.length === 0")->props(['style' => ['gridColumn' => '1 / -1', 'height' => '260px', 'display' => 'flex', 'alignItems' => 'center', 'justifyContent' => 'center']])
                ->children([$project ? '暂无匹配项目' : '暂无匹配模块']),
        ]);
    }

    public function detailModal(): Modal
    {
        return Modal::make()->props(['show' => '{{ marketDetailVisible }}', 'title' => '{{ marketDetailKind === "project" ? "项目详情" : "模块详情" }}',
            'style' => ['width' => '720px'], 'preset' => 'card'])->on('update:show', SetAction::make('marketDetailVisible', '{{ $event }}'))->children([
                Flex::make()->vertical()->props(['style' => ['gap' => '14px']])->children([
                    Html::div()->props(['style' => ['fontSize' => '18px', 'fontWeight' => 700]])->children(['{{ marketDetailItem?.title || "-" }}']),
                    Html::div()->children(['{{ marketDetailItem?.summary || "暂无简介" }}']),
                    Space::make()->props(['wrap' => true])->children([Tag::make()->children(['{{ marketDetailItem?.type_label || "-" }}']), Tag::make()->children(['版本 {{ marketDetailItem?.version || "-" }}'])]),
                    Space::make()->props(['justify' => 'end'])->children([
                        Button::make()->if('marketDetailKind === "module" && !marketDetailItem?.installed')->type('primary')->on('click', ['call' => 'handleInstallMarketModule'])->text('安装'),
                        Button::make()->if('marketDetailKind === "project"')->type('primary')->on('click', ['call' => 'handleInstallMarketProject'])->text('安装项目'),
                        Button::make()->on('click', SetAction::make('marketDetailVisible', false))->text('关闭'),
                    ]),
                ]),
            ]);
    }
}

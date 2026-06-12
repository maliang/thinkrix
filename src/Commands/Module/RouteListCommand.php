<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\Table;

/**
 * 模块路由列表命令
 *
 * 输出指定模块的所有已注册路由信息。
 *
 * 用法：
 *   php think thinkrix:module-route-list Blog
 */
class RouteListCommand extends BaseModuleCommand
{
    /**
     * 配置命令名称、描述和参数
     */
    protected function configure()
    {
        $this->setName('thinkrix:module-route-list')
            ->setDescription('列出指定模块的所有路由')
            ->addArgument('module', Argument::REQUIRED, '目标模块名称');
    }

    /**
     * 执行命令逻辑
     *
     * @param Input $input 输入实例
     * @param Output $output 输出实例
     * @return int 退出码（0=成功，1=失败）
     */
    protected function execute(Input $input, Output $output): int
    {
        $module = $input->getArgument('module');
        $generator = $this->getGenerator();
        $moduleName = $generator->studlyCase($module);

        // 验证模块存在
        if (!$this->validateModuleExists($moduleName, $output)) {
            return 1;
        }

        $modulePath = $generator->getModulePath($moduleName);
        $routeFile = $modulePath . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php';

        if (!file_exists($routeFile)) {
            $output->writeln("<comment>Module [{$moduleName}] has no route file.</comment>");
            return 0;
        }

        // 输出路由文件路径信息
        $output->writeln("<info>Routes for module [{$moduleName}]:</info>");
        $output->writeln("<comment>Route file: {$routeFile}</comment>");
        $output->writeln('');

        // 读取路由文件内容并解析路由定义
        $content = file_get_contents($routeFile);

        $routes = $this->parseRouteDefinitions($content);

        if (!empty($routes)) {
            $table = new Table();
            $table->setHeader(['Method', 'URI', 'Action']);
            $table->setRows($routes);

            $this->table($table);
        } else {
            $output->writeln("<comment>No recognizable route definitions found (may use group/resource patterns).</comment>");
            $output->writeln('');
            $output->writeln("Route file content:");
            $output->writeln($content);
        }

        return 0;
    }

    /**
     * 解析路由文件中的路由定义
     *
     * 通过正则匹配 Route::get/post/put/delete/patch/any/rule 模式，
     * 提取 HTTP 方法、URI 和 Action 信息。
     *
     * @param string $content 路由文件内容
     * @return array 解析后的路由行数据（二维数组）
     */
    protected function parseRouteDefinitions(string $content): array
    {
        $rows = [];

        // 匹配 Route::method('uri', 'action') 模式
        preg_match_all(
            '/Route::(get|post|put|delete|patch|any|rule)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/i',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $rows[] = [
                strtoupper($match[1]),
                $match[2],
                $match[3],
            ];
        }

        return $rows;
    }
}

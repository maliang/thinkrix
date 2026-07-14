<?php

namespace Thinkrix\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;

/** 创建或同步根目录 Trix 项目清单。 */
class ProjectMakeCommand extends Command
{
    /** 配置项目清单生成命令。 */
    protected function configure(): void
    {
        $this->setName('thinkrix:project-make')->setDescription('创建或同步根目录 trix-project.json')
            ->addOption('sync', null, Option::VALUE_NONE, '同步本地模块到已有清单')
            ->addOption('force', null, Option::VALUE_NONE, '覆盖已有清单')
            ->addOption('id', null, Option::VALUE_OPTIONAL, '项目 Registry ID')
            ->addOption('name', null, Option::VALUE_OPTIONAL, '项目名称')
            ->addOption('version', null, Option::VALUE_OPTIONAL, '项目版本', '1.0.0')
            ->addOption('author', null, Option::VALUE_OPTIONAL, '作者名称或邮箱');
    }

    /** 生成项目清单并返回退出状态。 */
    protected function execute(Input $input, Output $output): int
    {
        $path = app()->getRootPath() . 'trix-project.json';
        $exists = is_file($path);
        if ($exists && !$input->getOption('sync') && !$input->getOption('force')) {
            $output->writeln('<error>trix-project.json 已存在，请使用 --sync 或 --force。</error>');
            return 1;
        }

        $existing = $exists ? json_decode((string) file_get_contents($path), true) : [];
        if (!is_array($existing)) {
            $output->writeln('<error>trix-project.json 不是有效 JSON。</error>');
            return 1;
        }

        $manifest = array_merge([
            'schema_version' => 'trix.project.v1',
            'id' => $this->value($input, 'id', $existing['id'] ?? 'local.project'),
            'name' => $this->value($input, 'name', $existing['name'] ?? 'Local Project'),
            'version' => $this->value($input, 'version', $existing['version'] ?? '1.0.0'),
            'type' => 'starter', 'description' => '', 'logo' => '', 'thumbnail' => '',
            'author' => $this->value($input, 'author', $existing['author'] ?? ''),
            'author_url' => '', 'license' => 'MIT',
            'adapter' => ['language' => 'php', 'language_version' => '^8.2', 'framework' => 'thinkphp', 'framework_version' => '^8.0'],
            'modules' => [], 'bindings' => [], 'contract_bindings' => [], 'config' => [],
            'setup' => ['seeders' => [], 'commands' => []],
        ], $existing);
        $manifest['modules'] = $this->modules($existing['modules'] ?? []);

        if (file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX) === false) {
            $output->writeln('<error>无法写入 trix-project.json。</error>');
            return 1;
        }
        $output->writeln('<info>' . ($exists ? '已更新' : '已创建') . ' trix-project.json</info>');
        return 0;
    }

    /** 获取非空命令选项。 */
    private function value(Input $input, string $name, mixed $fallback): string
    {
        $value = trim((string) ($input->getOption($name) ?? ''));
        return $value !== '' ? $value : (string) $fallback;
    }

    /** 合并当前项目中声明了 module.json 的模块。 */
    private function modules(mixed $existing): array
    {
        $items = [];
        foreach (is_array($existing) ? $existing : [] as $module) {
            if (is_array($module) && is_string($module['id'] ?? null)) { $items[$module['id']] = $module; }
        }
        foreach (config('thinkrix.modules.paths', ['Modules']) as $root) {
            foreach (glob(app()->getRootPath() . trim($root, '/\\') . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'module.json') ?: [] as $file) {
                $module = json_decode((string) file_get_contents($file), true);
                $trix = is_array($module) && is_array($module['trix'] ?? null) ? $module['trix'] : null;
                if ($trix === null) { continue; }
                $id = trim((string) ($trix['id'] ?? basename(dirname($file))));
                $version = trim((string) ($trix['version'] ?? '1.0.0'));
                $parts = explode('.', $version);
                $items[$id] = array_merge(['id' => $id, 'version_constraint' => '^' . ($parts[0] ?? '1') . '.' . ($parts[1] ?? '0'), 'required' => true, 'config' => []], $items[$id] ?? []);
            }
        }
        return array_values($items);
    }
}

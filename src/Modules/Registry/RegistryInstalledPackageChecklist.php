<?php

namespace Thinkrix\Modules\Registry;

/** 根据模块清单生成安装后待办，提示迁移、配置发布等不会自动执行的动作。 */
class RegistryInstalledPackageChecklist
{
    /**
     * 构建当前流程使用的数据结构。
     * @return array<string, mixed>
     */
    public function build(string $modulePath, string $moduleId): array
    {
        $files = $this->relativeFiles($modulePath);
        $composerFiles = $this->matchingFiles($files, static fn (string $file): bool => $file === 'composer.json');
        $providerFiles = $this->matchingFiles($files, static fn (string $file): bool => $file === 'Service.php' || str_ends_with($file, 'ServiceProvider.php'));
        $migrationFiles = $this->matchingFiles($files, static fn (string $file): bool => str_contains($file, 'database/migrations/') && str_ends_with($file, '.php'));
        $seederFiles = $this->matchingFiles($files, static fn (string $file): bool => str_contains($file, 'database/seeders/') && str_ends_with($file, '.php'));

        $todos = ['Review copied module files before enabling the module.'];
        $commands = [];

        if ($composerFiles !== []) {
            $todos[] = 'Review composer.json and merge ThinkPHP service/autoload settings if needed.';
            $commands[] = 'Run composer dump-autoload after reviewing composer.json.';
        }

        if ($providerFiles !== []) {
            $todos[] = 'Review ThinkPHP service/provider files before enabling the module.';
        }

        if ($migrationFiles !== []) {
            $todos[] = 'Review database migrations before running them.';
            $commands[] = 'Run migrations manually after review, for example: php think migrate:run';
        }

        if ($seederFiles !== []) {
            $todos[] = 'Review database seeders before running them.';
            $commands[] = 'Run seeders manually after review, for example: php think seed:run';
        }

        return [
            'module_id' => $moduleId,
            'module_path' => $modulePath,
            'has_composer' => $composerFiles !== [],
            'provider_count' => count($providerFiles),
            'migration_count' => count($migrationFiles),
            'seeder_count' => count($seederFiles),
            'todos' => $todos,
            'commands' => $commands,
        ];
    }

    /**
     * 执行 relativeFiles 方法对应的具体职责。
     * @return array<int, string>
     */
    private function relativeFiles(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $this->collectFiles($root, $root, $files);
        sort($files);

        return $files;
    }

    /**
     * 执行 collectFiles 方法对应的具体职责。
     * @param array<int, string> $files
     */
    private function collectFiles(string $root, string $path, array &$files): void
    {
        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $this->collectFiles($root, $fullPath, $files);
                continue;
            }

            $files[] = str_replace(DIRECTORY_SEPARATOR, '/', substr($fullPath, strlen($root) + 1));
        }
    }

    /**
     * 执行 matchingFiles 方法对应的具体职责。
     * @param array<int, string> $files
     * @param callable(string): bool $predicate
     * @return array<int, string>
     */
    private function matchingFiles(array $files, callable $predicate): array
    {
        return array_values(array_filter($files, $predicate));
    }
}

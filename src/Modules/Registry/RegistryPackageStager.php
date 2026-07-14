<?php

namespace Thinkrix\Modules\Registry;

use ZipArchive;

/** 将已校验的发布包安全展开到独立暂存目录，正式模块目录保持不变。 */
class RegistryPackageStager
{
    /** 初始化当前对象及其依赖。 */
    public function __construct(private readonly ?string $stagingRoot = null)
    {
    }

    /**
     * 将已校验的发布包展开到暂存目录。
     * @return array<string, mixed>
     */
    public function stage(string $packagePath, string $moduleId, string $version): array
    {
        $preflight = (new RegistryPackagePreflightInspector())->inspect($packagePath);
        if (!$preflight['ok']) {
            return [
                'staged' => false,
                'reason' => $preflight['reason'],
                'message' => $preflight['message'],
                'path' => null,
                'manifest' => null,
            ];
        }

        $root = $this->rootPath();
        if (is_link($root)) {
            return $this->failure('staging_root_link_blocked', 'Registry staging root must not be a symbolic link.');
        }
        if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) {
            return $this->failure('staging_root_create_failed', 'Registry staging root could not be created.');
        }
        $rootPath = realpath($root);
        if (!is_string($rootPath)) {
            return $this->failure('staging_root_invalid', 'Registry staging root could not be resolved.');
        }

        $target = $this->targetPath($moduleId, $version);
        if (is_link($target) || !mkdir($target, 0700)) {
            return $this->failure('staging_target_create_failed', 'Registry staging target could not be created safely.');
        }
        $targetPath = realpath($target);
        if (!is_string($targetPath) || !str_starts_with($targetPath, $rootPath . DIRECTORY_SEPARATOR)) {
            return $this->failure('staging_target_outside_root', 'Registry staging target escaped its configured root.');
        }

        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            return $this->failure('zip_open_failed', 'Registry package is not a readable zip file.');
        }

        if (!$zip->extractTo($target)) {
            $zip->close();
            return $this->failure('zip_extract_failed', 'Registry package could not be extracted to staging.');
        }
        $zip->close();

        if ($this->containsSymbolicLink($target)) {
            $this->removeDirectory($target);
            return $this->failure('symbolic_link_blocked', 'Registry package extracted a symbolic link.');
        }

        $manifestPath = $target . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $preflight['manifest']);
        if (!is_file($manifestPath)) {
            return $this->failure('manifest_extract_missing', 'Registry package manifest was not found after staging.');
        }

        return [
            'staged' => true,
            'reason' => null,
            'message' => 'Registry package staged successfully.',
            'path' => $target,
            'manifest' => $preflight['manifest'],
        ];
    }

    /** 生成模块版本对应的暂存目录路径。 */
    private function targetPath(string $moduleId, string $version): string
    {
        return $this->rootPath() . DIRECTORY_SEPARATOR . $this->safeName($moduleId . '-' . $version)
            . '-' . bin2hex(random_bytes(8));
    }

    /** 解析模块包暂存根目录。 */
    private function rootPath(): string
    {
        return $this->stagingRoot ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-registry-staging';
    }

        /** 生成可安全用于文件系统的名称。 */
    private function safeName(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: 'package';
    }

        /** 移除指定目录或业务数据。 */
    private function removeDirectory(string $directory): void
    {
        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_link($path)) {
                unlink($path);
                continue;
            }
            if (is_dir($path)) {
                $this->removeDirectory($path);
                rmdir($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }

    /** 递归检查暂存目录中的符号链接。 */
    private function containsSymbolicLink(string $directory): bool
    {
        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_link($path) || (is_dir($path) && $this->containsSymbolicLink($path))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 执行 failure 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function failure(string $reason, string $message): array
    {
        return [
            'staged' => false,
            'reason' => $reason,
            'message' => $message,
            'path' => null,
            'manifest' => null,
        ];
    }
}

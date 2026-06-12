<?php

namespace Thinkrix\Support;

/**
 * Stub 模板解析器
 *
 * 负责查找、解析 Stub 模板文件并替换占位符变量。
 * 支持自定义 Stub 覆盖：优先使用项目 stubs/thinkrix-modules/ 目录下的模板，
 * 回退到包内 stubs/modules/ 目录。
 */
class StubResolver
{
    /**
     * 包内默认 Stub 目录
     */
    protected string $defaultStubPath;

    /**
     * 项目自定义 Stub 目录
     */
    protected string $customStubPath;

    public function __construct()
    {
        $this->defaultStubPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modules';
        $this->customStubPath = app()->getRootPath() . 'stubs' . DIRECTORY_SEPARATOR . 'thinkrix-modules';
    }

    /**
     * 解析 Stub 模板并替换占位符
     *
     * @param string $stubName Stub 文件名（不含路径，如 controller.stub）
     * @param array $replacements 占位符替换映射，键为占位符（如 {{MODULE_NAME}}），值为替换内容
     * @return string 解析后的内容
     */
    public function resolve(string $stubName, array $replacements): string
    {
        $stubPath = $this->getStubPath($stubName);

        if (!file_exists($stubPath)) {
            // 模板文件缺失，输出警告并返回空字符串
            $this->warning("Stub 模板文件 [{$stubName}] 不存在，回退使用内置默认模板。");
            return '';
        }

        $content = file_get_contents($stubPath);

        if ($content === false) {
            // 文件读取失败，输出警告
            $this->warning("Stub 模板文件 [{$stubName}] 读取失败，回退使用内置默认模板。");
            return '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * 获取 Stub 文件路径（优先自定义，回退默认）
     *
     * 查找优先级：
     * 1. 项目 stubs/thinkrix-modules/{stubName}
     * 2. 包内 stubs/modules/{stubName}
     *
     * @param string $stubName Stub 文件名
     * @return string 完整文件路径
     */
    public function getStubPath(string $stubName): string
    {
        // 优先查找项目自定义 Stub
        $customPath = $this->customStubPath . DIRECTORY_SEPARATOR . $stubName;
        if (file_exists($customPath)) {
            return $customPath;
        }

        // 回退到包内默认 Stub
        $defaultPath = $this->defaultStubPath . DIRECTORY_SEPARATOR . $stubName;
        return $defaultPath;
    }

    /**
     * 将默认 Stub 发布到项目目录
     *
     * 将包内 stubs/modules/ 下的所有 Stub 文件复制到
     * 项目的 stubs/thinkrix-modules/ 目录，供开发者自定义。
     *
     * @return array 已发布的文件列表（相对路径 => 目标路径）
     */
    public function publishStubs(): array
    {
        $published = [];

        if (!is_dir($this->defaultStubPath)) {
            return $published;
        }

        // 确保目标目录存在
        if (!is_dir($this->customStubPath)) {
            mkdir($this->customStubPath, 0755, true);
        }

        // 扫描默认 Stub 目录中的所有文件
        $files = glob($this->defaultStubPath . DIRECTORY_SEPARATOR . '*.stub');

        if ($files === false) {
            return $published;
        }

        foreach ($files as $file) {
            $filename = basename($file);
            $targetPath = $this->customStubPath . DIRECTORY_SEPARATOR . $filename;

            // 复制文件到目标目录（已存在则跳过）
            if (!file_exists($targetPath)) {
                copy($file, $targetPath);
            }

            $published[$filename] = $targetPath;
        }

        return $published;
    }

    /**
     * 获取支持的占位符列表
     *
     * @return array<string, string> 占位符 => 说明
     */
    public function getPlaceholders(): array
    {
        return [
            '{{MODULE_NAME}}' => 'StudlyCase 模块名（如 UserCenter）',
            '{{LOWER_NAME}}'  => '小写模块名（如 usercenter）',
            '{{NAMESPACE}}'   => '完整命名空间（如 app\\UserCenter\\controller）',
            '{{CLASS_NAME}}'  => '类名（如 UserController）',
            '{{TABLE_NAME}}'  => '数据表名（蛇形命名）',
            '{{TIMESTAMP}}'   => '迁移时间戳',
        ];
    }

    /**
     * 输出警告信息
     *
     * @param string $message 警告内容
     */
    protected function warning(string $message): void
    {
        // 使用 PHP 内置 trigger_error 发出警告
        trigger_error($message, E_USER_WARNING);
    }
}

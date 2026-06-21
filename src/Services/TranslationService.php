<?php

namespace Thinkrix\Services;

/**
 * TranslationService - 多语言翻译服务
 *
 * 负责合并包内语言包、项目语言包和模块语言包，
 * 提供统一的翻译 API 供前端消费。
 */
class TranslationService
{
    /**
     * 获取指定 locale 的全量翻译
     *
     * @param string $locale 语言代码，如 zh-cn、en-us
     * @return array 合并后的翻译数组
     */
    public function getTranslations(string $locale = 'zh-cn'): array
    {
        // 1. 包内语言包作为基础
        $translations = $this->loadPackageTranslations($locale);

        // 2. 合并项目 config/lang/ 下的覆盖
        $projectLang = $this->loadProjectTranslations($locale);
        $translations = array_replace_recursive($translations, $projectLang);

        // 3. 合并模块语言包（已启用模块）
        $moduleTranslations = $this->loadModuleTranslations($locale);
        $translations = array_replace_recursive($translations, $moduleTranslations);

        return $translations;
    }

    /**
     * 加载包内语言包
     */
    protected function loadPackageTranslations(string $locale): array
    {
        $file = __DIR__ . '/../../lang/' . $locale . '.php';
        if (!file_exists($file)) {
            // fallback 到英文
            $file = __DIR__ . '/../../lang/en-us.php';
        }
        if (!file_exists($file)) {
            return [];
        }
        $translations = include $file;
        return is_array($translations) ? $translations : [];
    }

    /**
     * 加载项目 config/lang/ 下的语言覆盖
     */
    protected function loadProjectTranslations(string $locale): array
    {
        $file = app()->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.php';
        if (!file_exists($file)) {
            return [];
        }
        $translations = include $file;
        return is_array($translations) ? $translations : [];
    }

    /**
     * 加载所有已启用模块的语言包
     */
    protected function loadModuleTranslations(string $locale): array
    {
        $translations = [];
        try {
            $modules = \Thinkrix\Models\Module::where('enabled', true)->select();
        } catch (\Throwable $e) {
            return [];
        }

        $paths = config('thinkrix.modules.paths', ['Modules', 'app']);
        $root = app()->getRootPath();

        foreach ($modules as $module) {
            foreach ($paths as $p) {
                $langFile = $root . $p . DIRECTORY_SEPARATOR . $module->name . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.php';
                if (file_exists($langFile)) {
                    $moduleLang = include $langFile;
                    if (is_array($moduleLang)) {
                        $translations = array_replace_recursive($translations, $moduleLang);
                    }
                    break;
                }
            }
        }

        return $translations;
    }
}

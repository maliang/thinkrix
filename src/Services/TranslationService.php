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
    protected static array $cache = [];

    public function getLanguages(): array
    {
        return config('thinkrix.languages', [
            'zh-CN' => ['label' => '中文', 'file' => 'zh-cn', 'naive_locale' => 'zh-CN'],
            'en-US' => ['label' => 'English', 'file' => 'en-us', 'naive_locale' => 'en-US'],
        ]);
    }

    public function normalizeLocale(string $locale): ?string
    {
        $normalized = strtolower(str_replace('_', '-', $locale));
        foreach (array_keys($this->getLanguages()) as $code) {
            if (strtolower(str_replace('_', '-', $code)) === $normalized) return $code;
        }
        return null;
    }

    public function getLanguageOptions(): array
    {
        $options = [];
        foreach ($this->getLanguages() as $code => $language) {
            $options[] = [
                'label' => $language['label'] ?? $code,
                'key' => $code,
                'naiveLocale' => $language['naive_locale'] ?? 'en-US',
            ];
        }
        return $options;
    }

    protected function getLanguageFileCode(string $locale): string
    {
        $canonical = $this->normalizeLocale($locale);
        if ($canonical !== null) {
            return $this->getLanguages()[$canonical]['file'] ?? strtolower(str_replace('_', '-', $canonical));
        }
        return strtolower(str_replace('_', '-', $locale));
    }

    /**
     * 获取指定 locale 的全量翻译
     *
     * @param string $locale 语言代码，如 zh-cn、en-us
     * @return array 合并后的翻译数组
     */
    public function getTranslations(string $locale = 'zh-cn'): array
    {
        $locale = $this->getLanguageFileCode($locale);
        if (isset(self::$cache[$locale])) {
            return self::$cache[$locale];
        }

        // 1. 包内语言包作为基础
        $translations = $this->loadPackageTranslations($locale);

        // 2. 合并项目 config/lang/ 下的覆盖
        $projectLang = $this->loadProjectTranslations($locale);
        $translations = array_replace_recursive($translations, $projectLang);

        // 3. 合并模块语言包（已启用模块）
        $moduleTranslations = $this->loadModuleTranslations($locale);
        $translations = array_replace_recursive($translations, $moduleTranslations);

        return self::$cache[$locale] = $translations;
    }

    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        $key = $this->compatibilityAliases()[$key] ?? $key;
        $locale = strtolower(str_replace('_', '-', $locale ?: config('thinkrix.locale', 'zh-CN')));
        $value = $this->getByPath($this->getTranslations($locale), $key);

        if (!is_string($value)) {
            $fallback = strtolower(str_replace('_', '-', config('thinkrix.fallback_locale', 'en-US')));
            $value = $this->getByPath($this->getTranslations($fallback), $key);
        }

        if (!is_string($value)) {
            return $key;
        }

        foreach ($replace as $name => $replacement) {
            $value = str_replace(':' . $name, (string) $replacement, $value);
        }

        return $value;
    }

    protected function compatibilityAliases(): array
    {
        return [
            'auth.failed' => 'auth.message.failed',
            'auth.login_ok' => 'auth.message.login_ok',
            'auth.logout_ok' => 'auth.message.logout_ok',
            'auth.refresh_ok' => 'auth.message.refresh_ok',
            'auth.revoke_ok' => 'auth.message.revoke_ok',
            'auth.token_not_found' => 'auth.message.token_not_found',
            'auth.password_incorrect' => 'auth.message.password_incorrect',
            'auth.password_mismatch' => 'auth.message.password_mismatch',
            'auth.password_changed' => 'auth.message.password_changed',
            'auth.password_reset_ok' => 'auth.message.password_reset_ok',
            'crud.created' => 'crud.message.created',
            'crud.updated' => 'crud.message.updated',
            'crud.deleted' => 'crud.message.deleted',
            'crud.status_updated' => 'crud.message.status_updated',
            'crud.batch_deleted' => 'crud.message.batch_deleted',
            'crud.sorted' => 'crud.message.sorted',
            'crud.order_updated' => 'crud.message.order_updated',
            'dict.column.enabled' => 'dict.column.is_enabled',
            'dict.title' => 'dict.page_title',
            'menu.column.schema_source' => 'menu.form.schema_source',
            'menu.resource_name' => 'menu.title',
            'menu.title.create' => 'menu.button.create',
            'menu.title.edit' => 'menu.button.edit',
            'module.not_found' => 'module.message.not_found',
            'module.enable_failed' => 'module.message.enable_failed',
            'module.enable_ok' => 'module.message.enabled',
            'module.disable_failed' => 'module.message.disable_failed',
            'module.disable_ok' => 'module.message.disabled',
            'module.install_failed' => 'module.message.install_failed',
            'module.install_ok' => 'module.message.installed',
            'module.uninstall_failed' => 'module.message.uninstall_failed',
            'module.uninstall_ok' => 'module.message.uninstalled',
            'notification.sent' => 'notification.message.sent',
            'notification.marked_read' => 'notification.message.marked_read',
            'notification.all_marked_read' => 'notification.message.all_marked_read',
            'permission.updated' => 'permission.message.updated',
            'permission.title.create' => 'permission.button.create',
            'permission.title.edit' => 'permission.button.edit',
            'role.title.create' => 'role.button.create',
            'role.title.edit' => 'role.button.edit',
            'system.builtin_not_deletable' => 'system.message.builtin_not_deletable',
            'system.config_saved' => 'system.message.config_saved',
            'user.page.title' => 'user.title',
            'user.resource_name' => 'user.title',
            'user.title.create' => 'user.button.create',
            'user.title.edit' => 'user.button.edit',
            'user.title.reset_password' => 'user.button.reset_password',
        ];
    }

    protected function getByPath(array $translations, string $key): mixed
    {
        $value = $translations;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return $value;
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
        $translations = is_array($translations) ? $translations : [];
        $extraFile = __DIR__ . '/../../lang/' . $locale . '-extra.php';
        if (file_exists($extraFile)) {
            $extra = include $extraFile;
            if (is_array($extra)) {
                $translations = array_replace_recursive($translations, $extra);
            }
        }
        return $translations;
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

<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use Thinkrix\Modules\Registry\RegistryInstalledPackageChecklist;
use Thinkrix\Modules\Registry\RegistryClient;
use Thinkrix\Modules\Registry\RegistryModuleReplacer;
use Thinkrix\Modules\Registry\RegistryPackageDownloader;
use Thinkrix\Modules\Registry\RegistryPackagePreflightInspector;
use Thinkrix\Modules\Registry\RegistryPackageStager;
use Thinkrix\Modules\Registry\RegistrySecurityAdvisory;
use Thinkrix\Modules\Registry\RegistryVersionResolver;
use Thinkrix\Modules\Registry\RegistryStagedPackageInstaller;
use Thinkrix\Modules\Registry\RegistryStagedManifestVerifier;
use Thinkrix\Services\ModuleService;
use Thinkrix\Models\Module;

/**
 * 模块安装命令
 *
 * 通过 CLI 安装指定模块，自动完成迁移、填充、菜单/权限注册。
 * 模块未启用状态下即可执行此命令（不依赖模块自己的命令注册）。
 *
 * 用法：
 *   php think thinkrix:module-install             安装所有未安装的模块
 *   php think thinkrix:module-install Blog        安装单个模块
 *   php think thinkrix:module-install Blog Shop   一次安装多个模块
 */
class InstallModuleCommand extends BaseModuleCommand
{
    /** 配置命令名称、参数和选项。 */
    protected function configure()
    {
        $this->setName('thinkrix:module-install')
            ->setDescription('安装模块（不传参数则安装所有未安装的模块）')
            ->addArgument('name', Argument::OPTIONAL | Argument::IS_ARRAY, '模块名称（可多个，不传则安装全部）')
            ->addOption('registry', null, Option::VALUE_OPTIONAL, 'Trix Module Registry base URL for future adapter resolution')
            ->addOption('download', null, Option::VALUE_NONE, 'Download registry adapter package to cache after checksum validation; does not install it')
            ->addOption('signature-key', null, Option::VALUE_OPTIONAL, 'Optional HMAC key for verifying registry adapter package signatures')
            ->addOption('from-stage', null, Option::VALUE_OPTIONAL, 'Copy a previously verified staging directory to a local module directory')
            ->addOption('manifest', null, Option::VALUE_OPTIONAL, 'Manifest path inside the staging directory for --from-stage')
            ->addOption('version', null, Option::VALUE_OPTIONAL, 'Expected module version for --from-stage verification')
            ->addOption('target-dir', null, Option::VALUE_OPTIONAL, 'Final local module directory for --from-stage or --replace-from-dir')
            ->addOption('replace-from-dir', null, Option::VALUE_OPTIONAL, 'Replace an existing local module directory with this reviewed source directory')
            ->addOption('backup-dir', null, Option::VALUE_OPTIONAL, 'Backup directory for --replace-from-dir; must not already exist')
            ->addOption('confirm-replace', null, Option::VALUE_NONE, 'Explicitly confirm replacing the target module directory');
    }

    /** 执行命令主流程并返回退出状态。 */
    protected function execute(Input $input, Output $output): int
    {
        $names = $input->getArgument('name');
        $registry = $this->registryUrl((string) ($input->getOption('registry') ?? ''));
        $download = (bool) $input->getOption('download');
        $signatureKey = $this->registrySignatureKey((string) ($input->getOption('signature-key') ?? ''));
        $moduleService = new ModuleService();

        // Registry 包分两步落地：先从已校验 staging 复制，再由人工执行 Thinkrix 安装流程。
        if ($input->getOption('from-stage')) {
            return $this->installFromStage($names, $input, $output);
        }

        // 替换已有模块属于高风险操作，必须显式传入源目录、备份目录和确认参数。
        if ($input->getOption('replace-from-dir')) {
            return $this->replaceFromDirectory($names, $input, $output);
        }

        // 不传参数则扫描所有模块目录
        if (empty($names)) {
            $failed = false;
            $paths = config('thinkrix.modules.paths', ['Modules', 'app']);
            $root = app()->getRootPath();
            foreach ($paths as $p) {
                $dir = $root . $p . DIRECTORY_SEPARATOR;
                if (!is_dir($dir)) { continue; }
                $items = scandir($dir);
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') { continue; }
                    $moduleDir = $dir . $item;
                    if (!is_dir($moduleDir)) { continue; }
                    if (!file_exists($moduleDir . DIRECTORY_SEPARATOR . 'module.json')) { continue; }
                    $failed = !$this->installSingle($item, $moduleService, $output, $registry, $download, $signatureKey) || $failed;
                }
            }
            return $failed ? 1 : 0;
        }

        $failed = false;
        foreach ($names as $name) {
            $moduleName = $this->normalizeModuleNameForInstall((string) $name, $registry);
            $failed = !$this->installSingle($moduleName, $moduleService, $output, $registry, $download, $signatureKey) || $failed;
        }

        return $failed ? 1 : 0;
    }

    /**
     * 备份旧目录并替换为新版本。
     * @param array<int, string> $names
     */
    protected function replaceFromDirectory(array $names, Input $input, Output $output): int
    {
        if (count($names) !== 1) {
            $output->writeln('<error>Exactly one registry module id is required when using --replace-from-dir.</error>');
            return 1;
        }

        $sourceDir = (string) $input->getOption('replace-from-dir');
        $manifest = (string) ($input->getOption('manifest') ?? '');
        $version = (string) ($input->getOption('version') ?? '');
        $targetDir = (string) ($input->getOption('target-dir') ?? '');
        $backupDir = (string) ($input->getOption('backup-dir') ?? '');
        $confirmed = (bool) $input->getOption('confirm-replace');

        if ($manifest === '' || $version === '' || $targetDir === '' || $backupDir === '') {
            $output->writeln('<error>--manifest, --version, --target-dir, and --backup-dir are required when using --replace-from-dir.</error>');
            return 1;
        }

        $moduleId = (string) $names[0];
        $verify = (new RegistryStagedManifestVerifier('php', 'thinkphp'))->verify($sourceDir, $manifest, $moduleId, $version);
        if (!$verify['ok']) {
            $output->writeln('<error>' . $verify['message'] . '</error>');
            return 1;
        }
        $this->printSecurityWarnings(is_array($verify['security'] ?? null) ? $verify['security'] : [], $output);

        // Replacer 会先备份当前目录，再移动新目录；这里不做迁移/Seeder，留给人工复核后执行。
        $replace = (new RegistryModuleReplacer())->replace($sourceDir, $targetDir, $backupDir, $confirmed);
        if (!$replace['replaced']) {
            $output->writeln('<error>' . $replace['message'] . '</error>');
            return 1;
        }

        $output->writeln('<info>Module directory replaced: ' . $replace['target_path'] . '</info>');
        $output->writeln('<info>Previous version backed up at: ' . $replace['backup_path'] . '</info>');
        $this->printPostCopyChecklist((new RegistryInstalledPackageChecklist())->build((string) $replace['target_path'], $moduleId), $output);
        $output->writeln('<comment>Module files were replaced only. Run the Thinkrix module install/enable/migration flow manually after review.</comment>');

        return 0;
    }

    /**
     * 执行模块或项目安装流程。
     * @param array<int, string> $names
     */
    protected function installFromStage(array $names, Input $input, Output $output): int
    {
        if (count($names) !== 1) {
            $output->writeln('<error>Exactly one registry module id is required when using --from-stage.</error>');
            return 1;
        }

        $stagePath = (string) $input->getOption('from-stage');
        $manifest = (string) ($input->getOption('manifest') ?? '');
        $version = (string) ($input->getOption('version') ?? '');
        $targetDir = (string) ($input->getOption('target-dir') ?? '');

        if ($manifest === '' || $version === '' || $targetDir === '') {
            $output->writeln('<error>--manifest, --version, and --target-dir are required when using --from-stage.</error>');
            return 1;
        }

        $moduleId = (string) $names[0];
        $verify = (new RegistryStagedManifestVerifier('php', 'thinkphp'))->verify($stagePath, $manifest, $moduleId, $version);
        if (!$verify['ok']) {
            $output->writeln('<error>' . $verify['message'] . '</error>');
            return 1;
        }
        $this->printSecurityWarnings(is_array($verify['security'] ?? null) ? $verify['security'] : [], $output);

        $install = (new RegistryStagedPackageInstaller())->install($stagePath, $manifest, $targetDir);
        if (!$install['installed']) {
            $output->writeln('<error>' . $install['message'] . '</error>');
            return 1;
        }

        $output->writeln('<info>Staged package copied to: ' . $install['path'] . '</info>');
        $this->printPostCopyChecklist((new RegistryInstalledPackageChecklist())->build((string) $install['path'], $moduleId), $output);
        $output->writeln('<comment>Module files were copied only. Run the Thinkrix module install/enable flow manually after review.</comment>');

        return 0;
    }

    /**
     * 向命令行输出当前流程信息。
     * @param array<string, mixed> $checklist
     */
    protected function printPostCopyChecklist(array $checklist, Output $output): void
    {
        $output->writeln('<comment>Review checklist:</comment>');
        foreach ($checklist['todos'] ?? [] as $todo) {
            $output->writeln('<comment>- ' . $todo . '</comment>');
        }

        if (!empty($checklist['commands'])) {
            $output->writeln('<comment>Suggested commands:</comment>');
            foreach ($checklist['commands'] as $command) {
                $output->writeln('<comment>- ' . $command . '</comment>');
            }
        }
    }

    /**
     * 向命令行输出当前流程信息。
     * @param array<string, mixed> $security
     */
    protected function printSecurityWarnings(array $security, Output $output): void
    {
        $warnings = (new RegistrySecurityAdvisory())->warnings($security);
        if ($warnings === []) {
            return;
        }

        $output->writeln('<comment>Security review required:</comment>');
        foreach ($warnings as $warning) {
            $output->writeln('<comment>- ' . $warning . '</comment>');
        }
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registryUrl(string $option): string
    {
        $option = trim($option);
        if ($option !== '') {
            return $option;
        }

        return trim((string) config('thinkrix.module_market.url', ''));
    }

        /** 处理 Registry 地址、认证或请求。 */
    protected function registrySignatureKey(string $option): string
    {
        $option = trim($option);
        if ($option !== '') {
            return $option;
        }

        return (string) config('thinkrix.module_market.signature_key', '');
    }

        /** 将输入值归一化为内部标准格式。 */
    protected function normalizeModuleNameForInstall(string $name, string $registry): string
    {
        if ($registry !== '') {
            return $name;
        }

        return $this->getGenerator()->studlyCase($name);
    }

        /** 执行模块或项目安装流程。 */
    protected function installSingle(string $moduleName, ModuleService $moduleService, Output $output, string $registry = '', bool $download = false, string $signatureKey = ''): bool
    {
        if ($registry !== '') {
            return $this->previewRegistryInstall($moduleName, $registry, $output, $download, $signatureKey);
        }

        $modulePath = $this->getGenerator()->getModulePath($moduleName);
        if (!is_dir($modulePath)) {
            $output->writeln("<error>Module [{$moduleName}] directory not found.</error>");
            return false;
        }
        if (!file_exists($modulePath . DIRECTORY_SEPARATOR . 'module.json')) {
            $output->writeln("<error>Module [{$moduleName}] module.json not found.</error>");
            return false;
        }

        $output->info("正在安装模块: {$moduleName}...");
        $result = $moduleService->install($moduleName);

        if ($result) {
            $output->writeln("<info>Module [{$moduleName}] installed successfully.</info>");
        } else {
            $output->writeln("<error>Module [{$moduleName}] installation failed.</error>");
        }

        return $result;
    }

    /** 查询 Registry 模块版本，并按需完成下载、预检和暂存。 */
    protected function previewRegistryInstall(string $moduleId, string $registry, Output $output, bool $download = false, string $signatureKey = ''): bool
    {
        $url = rtrim($registry, '/') . '/registry/modules/' . rawurlencode($moduleId) . '/versions?page_size=1&language=php&framework=thinkphp';
        $client = new RegistryClient($registry, trim((string) config('thinkrix.module_market.auth_key', '')));
        $lookup = $client->getJson('/registry/modules/' . rawurlencode($moduleId) . '/versions', [
            'page_size' => 1, 'language' => 'php', 'framework' => 'thinkphp']);
        if (!$lookup['ok']) {
            $output->writeln("<error>Registry module [{$moduleId}] lookup failed.</error>");
            return false;
        }

        // Registry 返回的是“模块版本 + 当前 adapter”，安装器只接受 php/thinkphp。
        $payload = $lookup['data'];
        if (!is_array($payload)) {
            $output->writeln("<error>Registry module [{$moduleId}] returned an invalid response.</error>");
            return false;
        }

        $result = (new RegistryVersionResolver('php', 'thinkphp'))->resolveLatest($payload);
        if (!$result['installable']) {
            $output->writeln('<error>' . $result['message'] . '</error>');
            return false;
        }

        $adapter = $result['adapter'];
        $version = $result['version'];
        $output->writeln("<info>Registry module [{$moduleId}] version [{$version['version']}] has an installable PHP/ThinkPHP adapter.</info>");
        $manifest = is_array($version['manifest'] ?? null) ? $version['manifest'] : [];
        $this->printSecurityWarnings(is_array($manifest['security'] ?? null) ? $manifest['security'] : [], $output);
        $output->writeln('<comment>Package type: ' . ($adapter['package_type'] ?? 'unknown') . '</comment>');
        if (!empty($adapter['package_url'])) {
            $output->writeln('<comment>Package URL: ' . $adapter['package_url'] . '</comment>');
        }

        if (!$download) {
            $output->writeln('<comment>Registry adapter download was not requested. Re-run with --download to cache the package after checksum validation.</comment>');
            return true;
        }

        // 下载阶段只缓存 zip，并校验 checksum/signature；真正复制目录必须另走 --from-stage。
        $downloadResult = (new RegistryPackageDownloader(fetcher: fn (string $url): ?string => $client->download($url), signatureKey: $signatureKey))->download(
            $adapter,
            $moduleId,
            (string) ($version['version'] ?? 'latest')
        );

        if (!$downloadResult['downloaded']) {
            $output->writeln('<error>' . $downloadResult['message'] . '</error>');
            return false;
        }

        $output->writeln('<info>Package cached at: ' . $downloadResult['path'] . '</info>');
        if (!empty($downloadResult['signature_reason'])) {
            $output->writeln('<info>Package signature verified: ' . $downloadResult['signature_reason'] . '</info>');
        }
        $preflight = (new RegistryPackagePreflightInspector())->inspect((string) $downloadResult['path']);
        if (!$preflight['ok']) {
            $output->writeln('<error>' . $preflight['message'] . '</error>');
            return false;
        }

        $output->writeln('<info>Package preflight passed. Manifest: ' . $preflight['manifest'] . '</info>');
        $output->writeln('<comment>Package files checked: ' . $preflight['file_count'] . '</comment>');

        $stage = (new RegistryPackageStager())->stage(
            (string) $downloadResult['path'],
            $moduleId,
            (string) ($version['version'] ?? 'latest')
        );

        if (!$stage['staged']) {
            $output->writeln('<error>' . $stage['message'] . '</error>');
            return false;
        }

        $output->writeln('<info>Package staged at: ' . $stage['path'] . '</info>');
        $verify = (new RegistryStagedManifestVerifier('php', 'thinkphp'))->verify(
            (string) $stage['path'],
            (string) $stage['manifest'],
            $moduleId,
            (string) ($version['version'] ?? 'latest')
        );

        if (!$verify['ok']) {
            $output->writeln('<error>' . $verify['message'] . '</error>');
            return false;
        }

        $output->writeln('<info>Staged manifest verified for PHP/ThinkPHP adapter: ' . $verify['adapter_status'] . '</info>');
        $this->printSecurityWarnings(is_array($verify['security'] ?? null) ? $verify['security'] : [], $output);
        $output->writeln('<comment>Registry package is staged only. Install or enable the ThinkPHP adapter through the local module flow.</comment>');
        return true;
    }
}

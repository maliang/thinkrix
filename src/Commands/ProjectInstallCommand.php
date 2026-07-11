<?php

namespace Thinkrix\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use Thinkrix\Modules\Project\ProjectInstallPlanStore;
use Thinkrix\Modules\Registry\RegistryInstalledPackageChecklist;
use Thinkrix\Modules\Registry\RegistryPackageDownloader;
use Thinkrix\Modules\Registry\RegistryPackageStager;
use Thinkrix\Modules\Registry\RegistryStagedManifestVerifier;
use Thinkrix\Modules\Registry\RegistryStagedPackageInstaller;

/** 按项目清单安装依赖模块，并落地项目配置与契约绑定。 */
class ProjectInstallCommand extends Command
{
    /** 配置命令名称、参数和选项。 */
    protected function configure(): void
    {
        $this->setName('thinkrix:project-install')
            ->setDescription('Install a Trix project plan by downloading and staging its module dependencies.')
            ->addArgument('project', Argument::OPTIONAL, 'Project registry id, not required when --plan is used')
            ->addOption('version', null, Option::VALUE_OPTIONAL, 'Project version, defaults to registry latest')
            ->addOption('registry', null, Option::VALUE_OPTIONAL, 'Registry API base URL')
            ->addOption('auth-key', null, Option::VALUE_OPTIONAL, 'Auth Key, defaults to TRIX_AUTH_KEY config')
            ->addOption('language', null, Option::VALUE_OPTIONAL, 'Adapter language', 'php')
            ->addOption('framework', null, Option::VALUE_OPTIONAL, 'Adapter framework', 'thinkphp')
            ->addOption('plan', null, Option::VALUE_OPTIONAL, 'Existing install-plan.json path')
            ->addOption('target-root', null, Option::VALUE_OPTIONAL, 'Directory where modules will be copied when --execute is set', 'Modules')
            ->addOption('audit-log', null, Option::VALUE_OPTIONAL, 'Optional JSONL audit log path')
            ->addOption('execute', null, Option::VALUE_NONE, 'Download, stage, verify and copy missing modules')
            ->addOption('dry-run', null, Option::VALUE_NONE, 'Resolve and save the plan without downloading modules');
    }

    /** 执行命令主流程并返回退出状态。 */
    protected function execute(Input $input, Output $output): int
    {
        $plan = $input->getOption('plan') ? $this->readPlan((string) $input->getOption('plan'), $output) : $this->fetchPlan($input, $output);
        if ($plan === null) {
            return 1;
        }

        $projectId = (string) ($plan['project'] ?? $input->getArgument('project') ?? 'project');
        $version = (string) ($plan['version'] ?? $input->getOption('version') ?? 'version');
        $paths = (new ProjectInstallPlanStore())->save($projectId, $version, $plan);

        // install-plan、项目覆盖配置、契约绑定分别落地，运行时可按需读取其中一部分。
        $output->writeln('<info>Project install plan saved: ' . $paths['install_plan'] . '</info>');
        $output->writeln('<comment>Project config: ' . $paths['project_config'] . '</comment>');
        $output->writeln('<comment>Contract bindings: ' . $paths['contract_bindings'] . '</comment>');

        if (($plan['install']['allowed'] ?? false) !== true) {
            $message = (string) ($plan['install']['reason'] ?? 'Project install plan is not allowed.');
            $output->writeln('<error>' . $message . '</error>');
            $this->writeAudit($input, $projectId, $version, 'blocked', ['message' => $message]);
            return 1;
        }

        $tasks = $this->buildTasks($input, $plan);
        $this->printTasks($tasks, $output);

        if (!$input->getOption('execute') || $input->getOption('dry-run')) {
            $output->writeln('<comment>Dry run only. Re-run with --execute to download and copy missing modules.</comment>');
            return 0;
        }

        $failed = false;
        foreach ($tasks as $task) {
            $result = $this->executeTask($input, $output, $task);
            $this->writeAudit($input, $projectId, $version, 'module', array_merge($task, $result));
            $failed = $failed || !($result['ok'] ?? false);
        }

        return $failed ? 1 : 0;
    }

    /**
     * 从指定来源读取数据。
     * @return array<string, mixed>|null
     */
    private function readPlan(string $path, Output $output): ?array
    {
        if (!is_file($path)) {
            $output->writeln('<error>Install plan not found: ' . $path . '</error>');
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            $output->writeln('<error>Install plan is not valid JSON.</error>');
            return null;
        }

        return $decoded;
    }

    /**
     * 从远端服务获取并解析数据。
     * @return array<string, mixed>|null
     */
    private function fetchPlan(Input $input, Output $output): ?array
    {
        $projectId = (string) ($input->getArgument('project') ?? '');
        if ($projectId === '') {
            $output->writeln('<error>Project registry id is required when --plan is not used.</error>');
            return null;
        }

        $registry = $this->registryUrl($input);
        if ($registry === '') {
            $output->writeln('<error>Please configure registry URL.</error>');
            return null;
        }

        $version = (string) ($input->getOption('version') ?? '');
        if ($version === '') {
            $url = $registry . '/registry/projects/' . rawurlencode($projectId) . '/versions?page_size=1&language=' . rawurlencode((string) $input->getOption('language')) . '&framework=' . rawurlencode((string) $input->getOption('framework'));
            $payload = $this->getJson($input, $url);
            $version = (string) ($payload['data']['items'][0]['version'] ?? '');
        }

        if ($version === '') {
            $output->writeln('<error>Project has no installable version.</error>');
            return null;
        }

        $url = $registry . '/registry/projects/' . rawurlencode($projectId) . '/versions/' . rawurlencode($version) . '/install-plan?language=' . rawurlencode((string) $input->getOption('language')) . '&framework=' . rawurlencode((string) $input->getOption('framework'));
        $payload = $this->getJson($input, $url);
        $plan = $payload['data'] ?? null;
        if (!is_array($plan)) {
            $output->writeln('<error>Registry returned an invalid install plan.</error>');
            return null;
        }

        return $plan;
    }

    /**
     * 构建当前流程使用的数据结构。
     * @return array<int, array<string, mixed>>
     */
    private function buildTasks(Input $input, array $plan): array
    {
        $tasks = [];
        foreach (($plan['modules'] ?? []) as $module) {
            if (!is_array($module) || !is_string($module['id'] ?? null)) {
                continue;
            }

            $moduleId = $module['id'];
            // 项目安装只处理当前 language/framework 的 adapter，避免把其他框架包复制进来。
            $tasks[] = [
                'id' => $moduleId,
                'version' => (string) ($module['selected_version'] ?? 'latest'),
                'required' => (bool) ($module['required'] ?? true),
                'allowed' => ($module['install']['allowed'] ?? false) === true,
                'reason' => (string) ($module['install']['reason'] ?? $module['constraint_reason'] ?? ''),
                'adapter' => is_array($module['adapter'] ?? null) ? $module['adapter'] : [],
                'target' => app()->getRootPath() . trim((string) $input->getOption('target-root'), '/\\') . DIRECTORY_SEPARATOR . $this->moduleDirectoryName($moduleId),
            ];
        }

        return $tasks;
    }

        /** 向命令行输出当前流程信息。 */
    private function printTasks(array $tasks, Output $output): void
    {
        $output->writeln('<comment>Module tasks:</comment>');
        foreach ($tasks as $task) {
            $status = $task['allowed'] ? 'installable' : 'blocked';
            $output->writeln("- {$task['id']} {$task['version']} [{$status}] -> {$task['target']}");
            if (!$task['allowed'] || $task['reason'] !== '') {
                $output->writeln('  ' . $task['reason']);
            }
        }
    }

    /**
     * 执行 executeTask 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function executeTask(Input $input, Output $output, array $task): array
    {
        if (!$task['allowed']) {
            $output->writeln('<error>Skipped blocked module: ' . $task['id'] . '</error>');
            return ['ok' => false, 'reason' => 'blocked'];
        }

        if (file_exists((string) $task['target'])) {
            // 一键项目安装默认不覆盖旧模块，升级必须走 module-update 的备份/审计流程。
            $output->writeln('<comment>Target exists, skipped without overwrite: ' . $task['target'] . '</comment>');
            return ['ok' => true, 'reason' => 'target_exists'];
        }

        $download = (new RegistryPackageDownloader(fetcher: fn (string $url): ?string => $this->fetchPackage($input, $url)))
            ->download($task['adapter'], (string) $task['id'], (string) $task['version']);
        if (!($download['downloaded'] ?? false)) {
            $output->writeln('<error>' . ($download['message'] ?? 'Package download failed.') . '</error>');
            return ['ok' => false, 'reason' => $download['reason'] ?? 'download_failed'];
        }

        $stage = (new RegistryPackageStager())->stage((string) $download['path'], (string) $task['id'], (string) $task['version']);
        if (!($stage['staged'] ?? false)) {
            $output->writeln('<error>' . ($stage['message'] ?? 'Package staging failed.') . '</error>');
            return ['ok' => false, 'reason' => $stage['reason'] ?? 'stage_failed'];
        }

        $verify = (new RegistryStagedManifestVerifier((string) $input->getOption('language'), (string) $input->getOption('framework')))
            ->verify((string) $stage['path'], (string) $stage['manifest'], (string) $task['id'], (string) $task['version']);
        if (!($verify['ok'] ?? false)) {
            $output->writeln('<error>' . ($verify['message'] ?? 'Staged manifest verification failed.') . '</error>');
            return ['ok' => false, 'reason' => $verify['reason'] ?? 'verify_failed'];
        }

        $install = (new RegistryStagedPackageInstaller())->install((string) $stage['path'], (string) $stage['manifest'], (string) $task['target']);
        if (!($install['installed'] ?? false)) {
            $output->writeln('<error>' . ($install['message'] ?? 'Staged package copy failed.') . '</error>');
            return ['ok' => false, 'reason' => $install['reason'] ?? 'install_failed'];
        }

        $output->writeln('<info>Installed module files: ' . $task['target'] . '</info>');
        foreach (((new RegistryInstalledPackageChecklist())->build((string) $task['target'], (string) $task['id'])['todos'] ?? []) as $todo) {
            $output->writeln('<comment>- ' . $todo . '</comment>');
        }

        return ['ok' => true, 'reason' => null];
    }

        /** 从远端服务获取并解析数据。 */
    private function fetchPackage(Input $input, string $url): ?string
    {
        $content = $this->httpGet($input, $url);

        return is_string($content) ? $content : null;
    }

    /**
     * 获取当前业务对象所需的数据。
     * @return array<string, mixed>
     */
    private function getJson(Input $input, string $url): array
    {
        $decoded = json_decode((string) $this->httpGet($input, $url), true);

        return is_array($decoded) ? $decoded : [];
    }

    /** 发送携带 Registry 认证信息的 HTTP GET 请求。 */
    private function httpGet(Input $input, string $url): string|false
    {
        $headers = "Accept: application/json\r\n";
        $authKey = $this->registryAuthKey($input);
        if ($authKey !== '') {
            $headers .= 'Authorization: Bearer ' . $authKey . "\r\n";
        }

        return @file_get_contents($url, false, stream_context_create(['http' => ['header' => $headers, 'timeout' => 60]]));
    }

        /** 处理 Registry 地址、认证或请求。 */
    private function registryUrl(Input $input): string
    {
        $option = trim((string) ($input->getOption('registry') ?? ''));

        return rtrim($option !== '' ? $option : (string) config('thinkrix.module_registry.url', ''), '/');
    }

        /** 处理 Registry 地址、认证或请求。 */
    private function registryAuthKey(Input $input): string
    {
        $option = trim((string) ($input->getOption('auth-key') ?? ''));

        return $option !== '' ? $option : trim((string) config('thinkrix.module_registry.auth_key', ''));
    }

    /**
     * 将数据写入指定存储位置。
     * @param array<string, mixed> $payload
     */
    private function writeAudit(Input $input, string $projectId, string $version, string $event, array $payload): void
    {
        $path = trim((string) ($input->getOption('audit-log') ?? ''));
        if ($path === '') {
            return;
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, json_encode([
            'time' => date(DATE_ATOM),
            'project' => $projectId,
            'version' => $version,
            'event' => $event,
            'payload' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }

        /** 执行 moduleDirectoryName 方法对应的具体职责。 */
    private function moduleDirectoryName(string $moduleId): string
    {
        return str_replace(' ', '', ucwords(str_replace(['.', '-', '_'], ' ', $moduleId)));
    }
}

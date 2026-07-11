<?php

namespace Thinkrix\Commands\Module;

use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use Thinkrix\Modules\Registry\RegistryInstalledPackageChecklist;
use Thinkrix\Modules\Registry\RegistryModuleUpdateAuditLogger;
use Thinkrix\Modules\Registry\RegistryModuleUpdateExecutor;
use Thinkrix\Modules\Registry\RegistrySecurityAdvisory;

/** 预览或执行已安装模块更新，并保留安全检查、备份和审计记录。 */
class UpdateModuleCommand extends BaseModuleCommand
{
    /** 配置命令名称、参数和选项。 */
    protected function configure()
    {
        $this->setName('thinkrix:module-update')
            ->setDescription('从已审核的 registry 包目录更新已安装模块')
            ->addArgument('module', Argument::REQUIRED, 'Registry module id')
            ->addOption('current-dir', null, Option::VALUE_OPTIONAL, 'Existing installed module directory containing module.json')
            ->addOption('source-dir', null, Option::VALUE_OPTIONAL, 'Reviewed target module directory or staging directory')
            ->addOption('manifest', null, Option::VALUE_OPTIONAL, 'Manifest path inside --source-dir', 'module.json')
            ->addOption('version', null, Option::VALUE_OPTIONAL, 'Expected target module version')
            ->addOption('backup-dir', null, Option::VALUE_OPTIONAL, 'Backup directory for current module; must not already exist')
            ->addOption('dry-run', null, Option::VALUE_NONE, 'Only print the update plan; do not replace directories')
            ->addOption('strict-security', null, Option::VALUE_NONE, 'Fail when manifest security flags require review')
            ->addOption('audit-log', null, Option::VALUE_OPTIONAL, 'Optional JSONL file path for update audit records')
            ->addOption('allow-downgrade', null, Option::VALUE_NONE, 'Explicitly allow replacing the current module with an older target version')
            ->addOption('confirm-replace', null, Option::VALUE_NONE, 'Explicitly confirm replacing the current module directory');
    }

    /** 执行命令主流程并返回退出状态。 */
    protected function execute(Input $input, Output $output): int
    {
        $moduleId = (string) $input->getArgument('module');
        $currentDir = (string) ($input->getOption('current-dir') ?? '');
        $sourceDir = (string) ($input->getOption('source-dir') ?? '');
        $manifest = (string) ($input->getOption('manifest') ?? 'module.json');
        $version = (string) ($input->getOption('version') ?? '');
        $backupDir = (string) ($input->getOption('backup-dir') ?? '');
        $dryRun = (bool) $input->getOption('dry-run');
        $strictSecurity = (bool) $input->getOption('strict-security');
        $auditLog = (string) ($input->getOption('audit-log') ?? '');
        $allowDowngrade = (bool) $input->getOption('allow-downgrade');
        $confirmed = (bool) $input->getOption('confirm-replace');

        if ($currentDir === '' || $sourceDir === '' || $manifest === '' || $version === '' || (!$dryRun && $backupDir === '')) {
            $output->writeln('<error>--current-dir, --source-dir, --manifest, --version, and --backup-dir are required. --backup-dir is optional only with --dry-run.</error>');
            return 1;
        }

        $executor = new RegistryModuleUpdateExecutor('php', 'thinkphp');

        if ($dryRun) {
            // dry-run 输出完整计划并写审计，便于在 CI 或后台 UI 中先做人工确认。
            $preview = $executor->preview($currentDir, $sourceDir, $manifest, $moduleId, $version, $allowDowngrade);
            $this->printUpdatePlan($preview, $output);
            $this->writeAudit($auditLog, $moduleId, 'dry_run', $preview, null, $currentDir, $sourceDir, $backupDir, $output);
            if ($strictSecurity && $this->securityBlocks(is_array($preview['security'] ?? null) ? $preview['security'] : [])) {
                $this->writeAudit($auditLog, $moduleId, 'strict_security_blocked', $preview, null, $currentDir, $sourceDir, $backupDir, $output);
                $output->writeln('<error>Strict security blocked this update plan.</error>');
                return 1;
            }

            return $preview['allowed'] ? 0 : 1;
        }

        if ($strictSecurity) {
            // strict-security 用于生产环境：manifest 声明写文件、命令或外部网络时直接阻断。
            $preview = $executor->preview($currentDir, $sourceDir, $manifest, $moduleId, $version, $allowDowngrade);
            if (!$preview['allowed']) {
                $this->printUpdatePlan($preview, $output);
                $this->writeAudit($auditLog, $moduleId, 'blocked', $preview, null, $currentDir, $sourceDir, $backupDir, $output);
                return 1;
            }

            if ($this->securityBlocks(is_array($preview['security'] ?? null) ? $preview['security'] : [])) {
                $this->printUpdatePlan($preview, $output);
                $this->writeAudit($auditLog, $moduleId, 'strict_security_blocked', $preview, null, $currentDir, $sourceDir, $backupDir, $output);
                $output->writeln('<error>Strict security blocked this update.</error>');
                return 1;
            }
        }

        $result = $executor->execute(
            $currentDir,
            $sourceDir,
            $manifest,
            $moduleId,
            $version,
            $backupDir,
            $confirmed,
            $allowDowngrade
        );

        if (!$result['updated']) {
            $tag = $result['action'] === 'already_current' ? 'comment' : 'error';
            $output->writeln('<' . $tag . '>' . $result['message'] . '</' . $tag . '>');
            $this->writeAudit($auditLog, $moduleId, 'not_updated', null, $result, $currentDir, $sourceDir, $backupDir, $output);
            return $result['action'] === 'already_current' ? 0 : 1;
        }

        $output->writeln('<info>Module updated from ' . $result['current_version'] . ' to ' . $result['target_version'] . '.</info>');
        $output->writeln('<info>Current module directory: ' . $result['target_path'] . '</info>');
        $output->writeln('<info>Backup directory: ' . $result['backup_path'] . '</info>');
        $this->printSecurityWarnings(is_array($result['security'] ?? null) ? $result['security'] : [], $output);
        $this->printPostCopyChecklist((new RegistryInstalledPackageChecklist())->build((string) $result['target_path'], $moduleId), $output);
        $output->writeln('<comment>Module files were replaced only. Run Thinkrix migrations, seeders, and cache/autoload refresh manually after review.</comment>');
        $this->writeAudit($auditLog, $moduleId, 'updated', null, $result, $currentDir, $sourceDir, $backupDir, $output);

        return 0;
    }

    /**
     * 将数据写入指定存储位置。
     * @param array<string, mixed>|null $preview
     * @param array<string, mixed>|null $result
     */
    protected function writeAudit(
        string $auditLog,
        string $moduleId,
        string $event,
        ?array $preview,
        ?array $result,
        string $currentDir,
        string $sourceDir,
        string $backupDir,
        Output $output
    ): void {
        if ($auditLog === '') {
            return;
        }

        // 审计日志采用 JSONL，方便追加写入，也方便后续按行导入后台或日志系统。
        $payload = $result ?? $preview ?? [];
        $write = (new RegistryModuleUpdateAuditLogger())->append($auditLog, [
            'event' => $event,
            'module_id' => $moduleId,
            'language' => 'php',
            'framework' => 'thinkphp',
            'action' => $payload['action'] ?? null,
            'message' => $payload['message'] ?? null,
            'current_version' => $payload['current_version'] ?? null,
            'target_version' => $payload['target_version'] ?? null,
            'current_dir' => $currentDir,
            'source_dir' => $sourceDir,
            'backup_dir' => $backupDir !== '' ? $backupDir : null,
            'target_path' => $payload['target_path'] ?? null,
            'backup_path' => $payload['backup_path'] ?? null,
            'security' => is_array($payload['security'] ?? null) ? $payload['security'] : [],
            'plan' => is_array($payload['plan'] ?? null) ? $payload['plan'] : null,
        ]);

        if (!$write['written']) {
            $output->writeln('<comment>Audit log was not written: ' . $write['message'] . '</comment>');
        }
    }

    /**
     * 执行 securityBlocks 方法对应的具体职责。
     * @param array<string, mixed> $security
     */
    protected function securityBlocks(array $security): bool
    {
        return (new RegistrySecurityAdvisory())->blocksStrict($security);
    }

    /**
     * 向命令行输出当前流程信息。
     * @param array<string, mixed> $preview
     */
    protected function printUpdatePlan(array $preview, Output $output): void
    {
        $output->writeln('<comment>Update plan: ' . $preview['action'] . '</comment>');
        $output->writeln('<comment>Current version: ' . ($preview['current_version'] ?? 'unknown') . '</comment>');
        $output->writeln('<comment>Target version: ' . ($preview['target_version'] ?? 'unknown') . '</comment>');
        $output->writeln('<comment>Allowed: ' . ($preview['allowed'] ? 'yes' : 'no') . '</comment>');
        $output->writeln('<comment>Message: ' . $preview['message'] . '</comment>');
        $this->printSecurityWarnings(is_array($preview['security'] ?? null) ? $preview['security'] : [], $output);
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
}

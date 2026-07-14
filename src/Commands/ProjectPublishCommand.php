<?php

namespace Thinkrix\Commands;

use ZipArchive;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;

/** 校验项目归属与版本后提交到 Trix Registry。 */
class ProjectPublishCommand extends Command
{
    /** 配置项目发布命令。 */
    protected function configure(): void
    {
        $this->setName('thinkrix:project-publish')->setDescription('发布根目录 trix-project.json')
            ->addOption('manifest', null, Option::VALUE_OPTIONAL, '项目清单路径', 'trix-project.json')
            ->addOption('registry', null, Option::VALUE_OPTIONAL, 'Registry API 地址')
            ->addOption('auth-key', null, Option::VALUE_OPTIONAL, 'TRIX_AUTH_KEY')
            ->addOption('dry-run', null, Option::VALUE_NONE, '仅校验，不提交');
    }

    /** 校验并发布项目。 */
    protected function execute(Input $input, Output $output): int
    {
        $path = app()->getRootPath() . ltrim((string) $input->getOption('manifest'), '/\\');
        $manifest = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (!is_array($manifest)) { $output->writeln('<error>项目清单不存在或不是有效 JSON。</error>'); return 1; }
        foreach (['schema_version', 'id', 'name', 'version', 'author'] as $field) {
            if (trim((string) ($manifest[$field] ?? '')) === '') { $output->writeln("<error>{$field} 为必填字段。</error>"); return 1; }
        }
        if ($manifest['schema_version'] !== 'trix.project.v1') { $output->writeln('<error>schema_version 必须为 trix.project.v1。</error>'); return 1; }

        $registry = rtrim(trim((string) ($input->getOption('registry') ?: config('thinkrix.module_market.url', ''))), '/');
        $authKey = trim((string) ($input->getOption('auth-key') ?: config('thinkrix.module_market.auth_key', '')));
        if ($registry === '' || $authKey === '') { $output->writeln('<error>请配置 Registry URL 和 TRIX_AUTH_KEY。</error>'); return 1; }
        $publisher = $this->request($registry . '/registry/auth/me', $authKey);
        $user = is_array($publisher['data']['user'] ?? null) ? $publisher['data']['user'] : [];
        $author = mb_strtolower(trim((string) $manifest['author']));
        $allowed = array_map(static fn ($v) => mb_strtolower(trim((string) $v)), [$user['name'] ?? '', $user['email'] ?? '']);
        if (!in_array($author, $allowed, true)) { $output->writeln('<error>项目作者必须与 Auth Key 用户名或邮箱一致。</error>'); return 1; }

        $versions = $this->request($registry . '/registry/projects/' . rawurlencode((string) $manifest['id']) . '/versions?page_size=1&language=php&framework=thinkphp', $authKey);
        $remote = $versions['data']['items'][0]['version'] ?? $versions['data']['version'] ?? null;
        if (is_string($remote) && !version_compare((string) $manifest['version'], $remote, '>')) {
            $output->writeln("<error>本地版本 {$manifest['version']} 必须高于市场版本 {$remote}。</error>"); return 1;
        }
        if ($input->getOption('dry-run')) { $output->writeln('<info>项目清单可以发布。</info>'); return 0; }

        $package = $this->createPackage($path);
        if ($package === null) { $output->writeln('<error>项目发布包创建失败。</error>'); return 1; }
        $result = $this->multipartRequest($registry . '/registry/publish/projects', $authKey, $manifest, $package);
        @unlink($package);
        if (($result['code'] ?? -1) !== 0) { $output->writeln('<error>' . ($result['msg'] ?? '项目发布失败。') . '</error>'); return 1; }
        $output->writeln('<info>' . ($result['msg'] ?? '项目已提交审核。') . '</info>');
        return 0;
    }

    /** 发送携带 Auth Key 的 Registry JSON 请求。 */
    private function request(string $url, string $authKey, ?array $body = null): array
    {
        $options = ['http' => ['method' => $body === null ? 'GET' : 'POST', 'timeout' => 60, 'ignore_errors' => true,
            'follow_location' => 0, 'max_redirects' => 0,
            'header' => "Accept: application/json\r\nAuthorization: Bearer {$authKey}\r\nContent-Type: application/json\r\n"]];
        if ($body !== null) { $options['http']['content'] = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
        $decoded = json_decode((string) @file_get_contents($url, false, stream_context_create($options)), true);
        return is_array($decoded) ? $decoded : [];
    }

    /** 创建仅包含项目清单的安全项目包；模块包由安装计划独立下载。 */
    private function createPackage(string $manifestPath): ?string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'thinkrix-project-' . bin2hex(random_bytes(8)) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { return null; }
        $added = $zip->addFile($manifestPath, 'trix-project.json');
        $closed = $zip->close();
        return $added && $closed ? $path : null;
    }

    /** 使用 multipart/form-data 上传项目清单和项目包。 */
    private function multipartRequest(string $url, string $authKey, array $manifest, string $package): array
    {
        $boundary = '----TrixBoundary' . bin2hex(random_bytes(12));
        $eol = "\r\n";
        $body = '--' . $boundary . $eol
            . 'Content-Disposition: form-data; name="manifest"' . $eol . $eol
            . json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . $eol
            . '--' . $boundary . $eol
            . 'Content-Disposition: form-data; name="package"; filename="project.zip"' . $eol
            . 'Content-Type: application/zip' . $eol . $eol
            . file_get_contents($package) . $eol
            . '--' . $boundary . '--' . $eol;
        $context = stream_context_create(['http' => [
            'method' => 'POST', 'timeout' => 60, 'ignore_errors' => true,
            'follow_location' => 0, 'max_redirects' => 0,
            'header' => "Accept: application/json\r\nAuthorization: Bearer {$authKey}\r\nContent-Type: multipart/form-data; boundary={$boundary}\r\nContent-Length: " . strlen($body) . "\r\n",
            'content' => $body,
        ]]);
        $decoded = json_decode((string) @file_get_contents($url, false, $context), true);
        return is_array($decoded) ? $decoded : [];
    }
}

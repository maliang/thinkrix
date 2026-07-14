<?php

namespace Thinkrix\Modules\Registry;

/** 统一处理模块市场地址、认证、超时和同源下载。 */
final class RegistryClient
{
    /** @var callable|null */
    private $transport;

    /** 注入可测试的传输器；返回 status、body 和 headers。 */
    public function __construct(
        private readonly ?string $url = null,
        private readonly ?string $authKey = null,
        private readonly ?int $timeout = null,
        ?callable $transport = null,
    ) {
        $this->transport = $transport;
    }

    /** 返回规范化后的市场根地址。 */
    public function baseUrl(): string
    {
        $configured = function_exists('config') ? (string) config('thinkrix.module_market.url', '') : '';
        return rtrim($this->url ?? $configured, '/');
    }

    /** 返回当前 Auth Key。 */
    public function authKey(): string
    {
        $configured = function_exists('config') ? (string) config('thinkrix.module_market.auth_key', '') : '';
        return trim($this->authKey ?? $configured);
    }

    /** 请求 JSON，并返回稳定结构。 */
    public function getJson(string $endpoint, array $query = []): array
    {
        if ($this->baseUrl() === '') {
            return $this->failure('registry_url_missing', '模块市场地址未配置。');
        }
        $url = $this->urlFor($endpoint) . ($query === [] ? '' : '?' . http_build_query($query));

        return $this->normalize($this->send('GET', $url));
    }

    /** 提交 JSON，并返回稳定结构。 */
    public function postJson(string $endpoint, array $payload = []): array
    {
        if ($this->baseUrl() === '') {
            return $this->failure('registry_url_missing', '模块市场地址未配置。');
        }

        return $this->normalize($this->send('POST', $this->urlFor($endpoint), $payload));
    }

    /** 仅从 Registry 同源地址下载，且禁止跟随重定向。 */
    public function download(string $url): ?string
    {
        if (!$this->isTrustedDownloadUrl($url)) {
            return null;
        }
        $response = $this->send('GET', $url);

        return ($response['status'] ?? 0) >= 200 && ($response['status'] ?? 0) < 300
            ? (is_string($response['body'] ?? null) ? $response['body'] : null)
            : null;
    }

    /** 拼接市场端点。 */
    public function urlFor(string $endpoint): string
    {
        return $this->baseUrl() . '/' . ltrim($endpoint, '/');
    }

    /** 执行不会跟随重定向的 HTTP 请求。 */
    private function send(string $method, string $url, array $payload = []): array
    {
        $headers = ['Accept: application/json'];
        if ($this->authKey() !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->authKey();
        }
        $options = [
            'headers' => $headers,
            'timeout' => $this->timeout ?? (function_exists('config') ? (int) config('thinkrix.module_market.timeout', 30) : 30),
            'follow_location' => 0,
            'max_redirects' => 0,
            'payload' => $payload,
        ];
        if (is_callable($this->transport)) {
            $result = ($this->transport)($method, $url, $options);
            return is_array($result) ? $result : ['status' => 0, 'body' => ''];
        }

        $context = ['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers) . "\r\n",
            'timeout' => $options['timeout'],
            'ignore_errors' => true,
            'follow_location' => 0,
            'max_redirects' => 0,
        ]];
        if ($method === 'POST') {
            $context['http']['header'] .= "Content-Type: application/json\r\n";
            $context['http']['content'] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $body = @file_get_contents($url, false, stream_context_create($context));
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                $status = (int) $matches[1];
            }
        }

        return ['status' => $status, 'body' => is_string($body) ? $body : ''];
    }

    /** 验证包地址与 Registry 的 scheme、host 和 port 完全一致。 */
    private function isTrustedDownloadUrl(string $url): bool
    {
        $base = parse_url($this->baseUrl());
        $target = parse_url($url);
        if (!is_array($base) || !is_array($target)) {
            return false;
        }
        $scheme = strtolower((string) ($target['scheme'] ?? ''));

        return in_array($scheme, ['http', 'https'], true)
            && $scheme === strtolower((string) ($base['scheme'] ?? ''))
            && strtolower((string) ($target['host'] ?? '')) === strtolower((string) ($base['host'] ?? ''))
            && ($target['port'] ?? null) === ($base['port'] ?? null);
    }

    /** 归一化 JSON 响应。 */
    private function normalize(array $response): array
    {
        $status = (int) ($response['status'] ?? 0);
        $data = json_decode((string) ($response['body'] ?? ''), true);
        $data = is_array($data) ? $data : [];
        if ($status < 200 || $status >= 300) {
            return $this->failure('registry_http_error', (string) ($data['msg'] ?? $data['message'] ?? "模块市场请求失败：HTTP {$status}"), $status, $data);
        }

        return ['ok' => true, 'status' => $status, 'data' => $data, 'reason' => null, 'message' => null];
    }

    /** 构造统一失败结构。 */
    private function failure(string $reason, string $message, int $status = 0, array $data = []): array
    {
        return compact('reason', 'message', 'status', 'data') + ['ok' => false];
    }
}

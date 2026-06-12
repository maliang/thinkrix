<?php

namespace Thinkrix\Schema\Actions;

/**
 * FetchAction - API 请求动作
 *
 * 对应 vschema 的 FetchAction 类型
 */
class FetchAction implements ActionInterface
{
    protected string $url;
    protected string $method = 'GET';
    protected array $headers = [];
    protected ?array $params = null;
    protected mixed $body = null;
    protected ?string $responseType = null;
    protected array $then = [];
    protected array $catch = [];
    protected array $finally = [];
    protected bool $ignoreBaseURL = false;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public static function make(string $url): static
    {
        return new static($url);
    }

    public function method(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function get(): static
    {
        return $this->method('GET');
    }

    public function post(): static
    {
        return $this->method('POST');
    }

    public function put(): static
    {
        return $this->method('PUT');
    }

    public function delete(): static
    {
        return $this->method('DELETE');
    }

    public function patch(): static
    {
        return $this->method('PATCH');
    }

    public function headers(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    public function params(array $params): static
    {
        $this->params = $params;
        return $this;
    }

    public function body(mixed $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function responseType(string $type): static
    {
        $this->responseType = $type;
        return $this;
    }

    public function ignoreBaseURL(bool $ignore = true): static
    {
        $this->ignoreBaseURL = $ignore;
        return $this;
    }

    public function then(ActionInterface|array $actions): static
    {
        $this->then = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    public function catch(ActionInterface|array $actions): static
    {
        $this->catch = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    public function finally(ActionInterface|array $actions): static
    {
        $this->finally = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    protected function normalizeActions(array $actions): array
    {
        return array_map(
            fn($a) => $a instanceof ActionInterface ? $a->toArray() : $a,
            $actions
        );
    }

    public function toArray(): array
    {
        $result = ['fetch' => $this->url];

        if ($this->method !== 'GET') {
            $result['method'] = $this->method;
        }

        if (!empty($this->headers)) {
            $result['headers'] = $this->headers;
        }

        if ($this->params !== null) {
            $result['params'] = $this->params;
        }

        if ($this->body !== null) {
            $result['body'] = $this->body;
        }

        if ($this->responseType !== null) {
            $result['responseType'] = $this->responseType;
        }

        if ($this->ignoreBaseURL) {
            $result['ignoreBaseURL'] = true;
        }

        if (!empty($this->then)) {
            $result['then'] = $this->normalizeActions($this->then);
        }

        if (!empty($this->catch)) {
            $result['catch'] = $this->normalizeActions($this->catch);
        }

        if (!empty($this->finally)) {
            $result['finally'] = $this->normalizeActions($this->finally);
        }

        return $result;
    }
}

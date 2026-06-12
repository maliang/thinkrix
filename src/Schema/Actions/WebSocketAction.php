<?php

namespace Thinkrix\Schema\Actions;

/**
 * WebSocketAction - WebSocket 长连接动作
 *
 * 对应 vschema 的 WebSocketAction 类型
 */
class WebSocketAction implements ActionInterface
{
    protected string $ws;
    protected ?string $op = null;
    protected ?string $id = null;
    protected string|array|null $protocols = null;
    protected ?int $timeout = null;
    protected mixed $message = null;
    protected ?string $sendAs = null;
    protected ?string $responseType = null;
    protected array $onOpen = [];
    protected array $onMessage = [];
    protected array $onError = [];
    protected array $onClose = [];
    protected array $then = [];
    protected array $catch = [];
    protected array $finally = [];
    protected ?int $code = null;
    protected ?string $reason = null;

    public function __construct(string $ws)
    {
        $this->ws = $ws;
    }

    public static function make(string $ws): static
    {
        return new static($ws);
    }

    public function op(string $op): static
    {
        $this->op = $op;
        return $this;
    }

    public function connect(): static
    {
        return $this->op('connect');
    }

    public function send(): static
    {
        return $this->op('send');
    }

    public function close(?int $code = null, ?string $reason = null): static
    {
        $this->op = 'close';
        $this->code = $code;
        $this->reason = $reason;
        return $this;
    }

    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function protocols(string|array $protocols): static
    {
        $this->protocols = $protocols;
        return $this;
    }

    public function timeout(int $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function message(mixed $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function sendAs(string $sendAs): static
    {
        $this->sendAs = $sendAs;
        return $this;
    }

    public function responseType(string $responseType): static
    {
        $this->responseType = $responseType;
        return $this;
    }

    public function onOpen(ActionInterface|array $actions): static
    {
        $this->onOpen = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    public function onMessage(ActionInterface|array $actions): static
    {
        $this->onMessage = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    public function onError(ActionInterface|array $actions): static
    {
        $this->onError = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    public function onClose(ActionInterface|array $actions): static
    {
        $this->onClose = is_array($actions) ? $actions : [$actions];
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
        $result = ['ws' => $this->ws];

        if ($this->op !== null) $result['op'] = $this->op;
        if ($this->id !== null) $result['id'] = $this->id;
        if ($this->protocols !== null) $result['protocols'] = $this->protocols;
        if ($this->timeout !== null) $result['timeout'] = $this->timeout;
        if ($this->message !== null) $result['message'] = $this->message;
        if ($this->sendAs !== null) $result['sendAs'] = $this->sendAs;
        if ($this->responseType !== null) $result['responseType'] = $this->responseType;

        if (!empty($this->onOpen)) $result['onOpen'] = $this->normalizeActions($this->onOpen);
        if (!empty($this->onMessage)) $result['onMessage'] = $this->normalizeActions($this->onMessage);
        if (!empty($this->onError)) $result['onError'] = $this->normalizeActions($this->onError);
        if (!empty($this->onClose)) $result['onClose'] = $this->normalizeActions($this->onClose);

        if (!empty($this->then)) $result['then'] = $this->normalizeActions($this->then);
        if (!empty($this->catch)) $result['catch'] = $this->normalizeActions($this->catch);
        if (!empty($this->finally)) $result['finally'] = $this->normalizeActions($this->finally);

        if ($this->code !== null) $result['code'] = $this->code;
        if ($this->reason !== null) $result['reason'] = $this->reason;

        return $result;
    }
}

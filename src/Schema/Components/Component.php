<?php

namespace Thinkrix\Schema\Components;

use Thinkrix\Schema\JsonNodeInterface;
use Thinkrix\Schema\Actions\ActionInterface;

/**
 * Component 基类
 *
 * 对应 vschema 的 JsonNode 类型
 */
abstract class Component implements JsonNodeInterface
{
    // === Component Rendering ===
    protected ?string $com = null;
    protected array $props = [];
    protected array $children = [];
    protected array $events = [];
    protected array $slots = [];

    // === Directives ===
    protected ?string $if = null;
    protected ?string $show = null;
    protected ?string $for = null;
    protected ?string $key = null;
    protected string|array|null $model = null;
    protected ?string $ref = null;

    // === Data and Logic ===
    protected array $data = [];
    protected array $computed = [];
    protected array $watch = [];
    protected array $methods = [];

    // === Lifecycle Hooks ===
    protected array $onMounted = [];
    protected array $onUnmounted = [];
    protected array $onUpdated = [];

    // === API Configuration ===
    protected string|array|null $initApi = null;
    protected string|array|null $uiApi = null;

    public function __construct(?string $com = null)
    {
        $this->com = $com;
    }

    // === Component Rendering Methods ===

    public function props(array $props): static
    {
        $this->props = array_merge($this->props, $props);
        return $this;
    }

    public function children(array|string|JsonNodeInterface $children): static
    {
        if ($children instanceof JsonNodeInterface) {
            $this->children = [$children];
        } elseif (is_array($children)) {
            $this->children = $children;
        } else {
            $this->children = [$children];
        }
        return $this;
    }

    public function on(string $event, ActionInterface|array $handler): static
    {
        $this->events[$event] = $handler;
        return $this;
    }

    public function slot(string $name, array $content, ?string $slotProps = null): static
    {
        $this->slots[$name] = $slotProps
            ? ['content' => $content, 'slotProps' => $slotProps]
            : $content;
        return $this;
    }

    // === Directives Methods ===

    public function if(string $expression): static
    {
        $this->if = $expression;
        return $this;
    }

    public function show(string $expression): static
    {
        $this->show = $expression;
        return $this;
    }

    public function for(string $expression, ?string $key = null): static
    {
        $this->for = $expression;
        if ($key) {
            $this->key = $key;
        }
        return $this;
    }

    public function model(string|array $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function ref(string $name): static
    {
        $this->ref = $name;
        return $this;
    }

    // === Data and Logic Methods ===

    public function data(array $data): static
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function computed(array $computed): static
    {
        $this->computed = array_merge($this->computed, $computed);
        return $this;
    }

    public function methods(array $methods): static
    {
        $this->methods = array_merge($this->methods, $methods);
        return $this;
    }

    public function watch(string $path, ActionInterface|array $handler, bool $immediate = false, bool $deep = false): static
    {
        $config = ['handler' => $handler];
        if ($immediate) {
            $config['immediate'] = true;
        }
        if ($deep) {
            $config['deep'] = true;
        }
        $this->watch[$path] = $config;
        return $this;
    }

    // === Lifecycle Hooks Methods ===

    public function onMounted(ActionInterface|array $actions): static
    {
        $this->onMounted = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    public function onUnmounted(ActionInterface|array $actions): static
    {
        $this->onUnmounted = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    public function onUpdated(ActionInterface|array $actions): static
    {
        $this->onUpdated = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    // === API Configuration Methods ===

    public function initApi(string|array $config): static
    {
        $this->initApi = $config;
        return $this;
    }

    public function uiApi(string|array $config): static
    {
        $this->uiApi = $config;
        return $this;
    }

    // === Output Methods ===

    public function toArray(): array
    {
        $result = [];

        // Component Rendering
        if ($this->com) {
            $result['com'] = $this->com;
        }
        if (!empty($this->props)) {
            $result['props'] = $this->props;
        }
        if (!empty($this->children)) {
            $result['children'] = $this->normalizeChildren($this->children);
        }
        if (!empty($this->events)) {
            $result['events'] = $this->normalizeEvents($this->events);
        }
        if (!empty($this->slots)) {
            $result['slots'] = $this->normalizeSlots($this->slots);
        }

        // Directives
        if ($this->if) {
            $result['if'] = $this->if;
        }
        if ($this->show) {
            $result['show'] = $this->show;
        }
        if ($this->for) {
            $result['for'] = $this->for;
        }
        if ($this->key) {
            $result['key'] = $this->key;
        }
        if ($this->model) {
            $result['model'] = $this->model;
        }
        if ($this->ref) {
            $result['ref'] = $this->ref;
        }

        // Data and Logic
        if (!empty($this->data)) {
            $result['data'] = $this->data;
        }
        if (!empty($this->computed)) {
            $result['computed'] = $this->computed;
        }
        if (!empty($this->watch)) {
            $result['watch'] = $this->normalizeWatch($this->watch);
        }
        if (!empty($this->methods)) {
            $result['methods'] = $this->normalizeMethods($this->methods);
        }

        // Lifecycle Hooks
        if (!empty($this->onMounted)) {
            $result['onMounted'] = $this->normalizeActions($this->onMounted);
        }
        if (!empty($this->onUnmounted)) {
            $result['onUnmounted'] = $this->normalizeActions($this->onUnmounted);
        }
        if (!empty($this->onUpdated)) {
            $result['onUpdated'] = $this->normalizeActions($this->onUpdated);
        }

        // API Configuration
        if ($this->initApi) {
            $result['initApi'] = $this->initApi;
        }
        if ($this->uiApi) {
            $result['uiApi'] = $this->uiApi;
        }

        return $result;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // === Helper Methods ===

    protected function normalizeChildren(array $children): array|string
    {
        if (count($children) === 1 && is_string($children[0])) {
            return $children[0];
        }

        return array_map(
            fn($c) => $c instanceof JsonNodeInterface ? $c->toArray() : $c,
            $children
        );
    }

    protected function normalizeEvents(array $events): array
    {
        return array_map(function ($h) {
            if ($h instanceof ActionInterface) {
                if ($h instanceof \Thinkrix\Schema\Actions\SetAction && $h->isBatch()) {
                    return $h->toArray();
                }
                return $h->toArray();
            }

            if (\is_array($h)) {
                if (isset($h['call']) || isset($h['set']) || isset($h['fetch']) || isset($h['emit'])
                    || isset($h['script']) || isset($h['if']) || isset($h['copy']) || isset($h['ws'])) {
                    return $h;
                }

                $result = [];
                foreach ($h as $a) {
                    if ($a instanceof ActionInterface) {
                        if ($a instanceof \Thinkrix\Schema\Actions\SetAction && $a->isBatch()) {
                            foreach ($a->toArray() as $item) {
                                $result[] = $item;
                            }
                        } else {
                            $result[] = $a->toArray();
                        }
                    } else {
                        $result[] = $a;
                    }
                }
                return $result;
            }

            return $h;
        }, $events);
    }

    protected function normalizeSlots(array $slots): array
    {
        return array_map(function ($s) {
            if (isset($s['content'])) {
                return [
                    'content' => array_map(
                        fn($n) => $n instanceof JsonNodeInterface ? $n->toArray() : $n,
                        $s['content']
                    ),
                    'slotProps' => $s['slotProps'] ?? null,
                ];
            }
            return array_map(
                fn($n) => $n instanceof JsonNodeInterface ? $n->toArray() : $n,
                $s
            );
        }, $slots);
    }

    protected function normalizeWatch(array $watch): array
    {
        return array_map(function ($c) {
            if (isset($c['handler'])) {
                $c['handler'] = $this->normalizeActions(
                    is_array($c['handler']) ? $c['handler'] : [$c['handler']]
                );
            }
            return $c;
        }, $watch);
    }

    protected function normalizeMethods(array $methods): array
    {
        return array_map(
            fn($a) => $this->normalizeActions(is_array($a) ? $a : [$a]),
            $methods
        );
    }

    protected function normalizeActions(array $actions): array
    {
        $result = [];
        foreach ($actions as $a) {
            if ($a instanceof ActionInterface) {
                if ($a instanceof \Thinkrix\Schema\Actions\SetAction && $a->isBatch()) {
                    foreach ($a->toArray() as $item) {
                        $result[] = $item;
                    }
                } else {
                    $result[] = $a->toArray();
                }
            } else {
                $result[] = $a;
            }
        }
        return $result;
    }
}

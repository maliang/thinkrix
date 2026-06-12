<?php

namespace Thinkrix\Schema\Components\Business;

use Thinkrix\Schema\Components\Component;
use Thinkrix\Schema\Components\NaiveUI\Form;
use Thinkrix\Schema\Components\NaiveUI\FormItem;
use Thinkrix\Schema\Components\NaiveUI\Space;
use Thinkrix\Schema\Components\NaiveUI\Button;
use Thinkrix\Schema\JsonNodeInterface;

/**
 * OptForm - 简化表单组件
 */
class OptForm implements JsonNodeInterface
{
    protected string $modelPath;
    protected array $fields = [];
    protected array $buttons = [];
    protected array $formProps = [];
    protected array $extraChildren = [];

    public function __construct(string $modelPath = 'formData')
    {
        $this->modelPath = $modelPath;
        $this->formProps = ['labelPlacement' => 'left', 'labelWidth' => 80];
    }

    public static function make(string $modelPath = 'formData'): static
    {
        return new static($modelPath);
    }

    public function fields(array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    public function buttons(array $buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }

    public function props(array $props): static
    {
        $this->formProps = array_merge($this->formProps, $props);
        return $this;
    }

    public function labelWidth(int|string $width): static
    {
        $this->formProps['labelWidth'] = $width;
        return $this;
    }

    public function labelPlacement(string $placement): static
    {
        $this->formProps['labelPlacement'] = $placement;
        return $this;
    }

    public function append(Component|array $children): static
    {
        if (is_array($children)) {
            $this->extraChildren = array_merge($this->extraChildren, $children);
        } else {
            $this->extraChildren[] = $children;
        }
        return $this;
    }

    public function getDefaultData(): array
    {
        $data = [];
        foreach ($this->fields as $field) {
            $name = $field[1];
            $default = $field[3] ?? '';
            $data[$name] = $default;
        }
        return $data;
    }

    public function getModelPath(): string
    {
        return $this->modelPath;
    }

    public function toArray(): array
    {
        $formItems = [];
        foreach ($this->fields as $field) {
            $label = $field[0];
            $name = $field[1];
            $component = $field[2];
            $componentArray = $component->toArray();
            if (!isset($componentArray['model']) || !is_array($componentArray['model'])) {
                $component->model("{$this->modelPath}.{$name}");
            }
            $formItem = FormItem::make()->label($label);
            if (isset($field[4]) && is_string($field[4])) {
                $formItem->if($field[4]);
            }
            $formItem->children([$component]);
            $formItems[] = $formItem;
        }
        foreach ($this->extraChildren as $child) {
            if ($child instanceof JsonNodeInterface) {
                $formItems[] = $child;
            }
        }
        if (!empty($this->buttons)) {
            $formItems[] = FormItem::make()->children([
                Space::make()->props(['justify' => 'end'])->children($this->buttons),
            ]);
        }
        return Form::make()->props($this->formProps)->children($formItems)->toArray();
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

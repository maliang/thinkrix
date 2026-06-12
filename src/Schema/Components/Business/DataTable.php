<?php

namespace Thinkrix\Schema\Components\Business;

use Thinkrix\Schema\Components\Component;

/**
 * DataTable - 数据表格组件
 *
 * 对应前端 JsonDataTable 的封装，用于支持 JSON Schema 列插槽。
 */
class DataTable extends Component
{
    protected array $tableSlots = [];

    public function __construct()
    {
        parent::__construct('JsonDataTable');
    }

    public static function make(): static
    {
        return new static();
    }

    public function dataSource(string $dataSource): static
    {
        return $this->props(['data' => "{{ {$dataSource} }}"]);
    }

    public function loading(string $loading): static
    {
        return $this->props(['loading' => "{{ {$loading} }}"]);
    }

    public function rowKey(string $rowKey): static
    {
        return $this->props(['rowKey' => "{{ row => row.{$rowKey} }}"]);
    }

    public function columns(array|string $columns): static
    {
        if (is_string($columns)) {
            return $this->props(['columns' => "{{ {$columns} }}"]);
        }

        $processedColumns = [];
        $this->tableSlots = [];

        foreach ($columns as $col) {
            if (isset($col['slot'])) {
                $this->tableSlots[$col['key']] = [
                    'content' => $col['slot'],
                    'slotProps' => $col['slotProps'] ?? 'slotData',
                ];
                unset($col['slot'], $col['slotProps']);
            }
            $processedColumns[] = $col;
        }

        $this->props(['columns' => $processedColumns]);

        foreach ($this->tableSlots as $column => $config) {
            $this->slot($column, $config['content'], $config['slotProps']);
        }

        return $this;
    }

    public function pagination(array|bool $pagination): static
    {
        return $this->props(['pagination' => $pagination]);
    }

    public function scrollX(int $x): static
    {
        return $this->props(['scrollX' => $x]);
    }

    public function flexHeight(bool $flex = true): static
    {
        return $this->props(['flexHeight' => $flex]);
    }

    public function virtualScroll(bool $virtual = true): static
    {
        return $this->props(['virtualScroll' => $virtual]);
    }

    public function singleLine(bool $single = true): static
    {
        return $this->props(['singleLine' => $single]);
    }

    public function striped(bool $striped = true): static
    {
        return $this->props(['striped' => $striped]);
    }

    public function bordered(bool $bordered = true): static
    {
        return $this->props(['bordered' => $bordered]);
    }

    public function size(string $size): static
    {
        return $this->props(['size' => $size]);
    }

    public function maxHeight(string|int $height): static
    {
        return $this->props(['maxHeight' => $height]);
    }

    public function minHeight(string|int $height): static
    {
        return $this->props(['minHeight' => $height]);
    }

    public function remote(bool $remote = true): static
    {
        return $this->props(['remote' => $remote]);
    }

    public function singleColumnSorting(bool $single = true): static
    {
        return $this->props(['singleColumnSorting' => $single]);
    }
}

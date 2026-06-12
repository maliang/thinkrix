<?php

namespace Thinkrix\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use think\Collection;

/**
 * BaseExport - 通用导出基类
 *
 * 使用 PhpSpreadsheet 实现数据导出，替代 maatwebsite/excel
 */
class BaseExport
{
    protected Collection $data;
    protected array $columns;
    protected array $headings = [];
    protected array $keys = [];

    /**
     * @param Collection $data 要导出的数据
     * @param array $columns 列配置，格式：[['key' => 'id', 'title' => 'ID'], ...]
     */
    public function __construct(Collection $data, array $columns)
    {
        $this->data = $data;
        $this->columns = $columns;
        $this->parseColumns();
    }

    /**
     * 解析列配置
     */
    protected function parseColumns(): void
    {
        foreach ($this->columns as $col) {
            if (in_array($col['key'] ?? '', ['actions', 'selection']) || ($col['type'] ?? '') === 'selection') {
                continue;
            }
            $this->headings[] = $col['title'] ?? $col['key'];
            $this->keys[] = $col['key'];
        }
    }

    /**
     * 下载导出文件
     *
     * @param string $filename 文件名
     * @return \think\Response
     */
    public function download(string $filename = 'export.xlsx')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 设置表头
        $colIndex = 1;
        foreach ($this->headings as $heading) {
            $coordinate = Coordinate::stringFromColumnIndex($colIndex) . '1';
            $cell = $sheet->getCell($coordinate);
            $cell->setValue($heading);

            // 样式
            $style = $sheet->getStyle($coordinate);
            $style->getFont()->setBold(true);
            $style->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF0F0F0');
            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            $colIndex++;
        }

        // 填充数据
        $rowIndex = 2;
        foreach ($this->data as $row) {
            $colIndex = 1;
            foreach ($this->keys as $key) {
                $value = $row[$key] ?? '';

                // 处理特殊类型
                if ($value instanceof Collection || is_array($value)) {
                    $items = is_array($value) ? $value : $value->toArray();
                    $value = implode(', ', array_map(function ($item) {
                        if (is_array($item)) {
                            return $item['title'] ?? $item['name'] ?? '';
                        } elseif (is_object($item) && method_exists($item, '__toString')) {
                            return (string) $item;
                        }
                        return '';
                    }, $items));
                } elseif (is_bool($value)) {
                    $value = $value ? '是' : '否';
                }

                $coordinate = Coordinate::stringFromColumnIndex($colIndex) . $rowIndex;
                $cell = $sheet->getCell($coordinate);
                $cell->setValue((string) $value);
                $colIndex++;
            }
            $rowIndex++;
        }

        // 自动调整列宽
        foreach (range(1, count($this->headings)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        // 输出文件
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        $writer->save($tempFile);

        $content = file_get_contents($tempFile);
        unlink($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * 从数据集合创建导出实例
     */
    public static function fromCollection(Collection $data, array $columns): static
    {
        return new static($data, $columns);
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TableExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private string $title,
        private array $columns,
        private array $rows,
        private array $summary = [],
    ) {}

    public function headings(): array
    {
        return $this->columns;
    }

    public function array(): array
    {
        $data = $this->rows;

        if (! empty($this->summary)) {
            $data[] = array_fill(0, max(2, count($this->columns)), ''); // baris kosong pemisah
            foreach ($this->summary as $label => $value) {
                $row = array_fill(0, max(2, count($this->columns)), '');
                $row[0] = $label;
                $row[1] = (string) $value;
                $data[] = $row;
            }
        }

        return $data;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31); // batas panjang nama sheet Excel
    }

    public function styles(Worksheet $sheet)
    {
        // Baris header tebal + latar hijau primary + teks putih
        $sheet->getStyle(1)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle(1)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0C5547');
        return [];
    }
}

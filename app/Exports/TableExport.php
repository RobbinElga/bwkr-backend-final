<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
            $data[] = array_fill(0, max(2, count($this->columns)), ''); // pemisah
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
        return mb_substr($this->title, 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol  = $sheet->getHighestColumn();
        $lastRow  = $sheet->getHighestRow();
        $dataRows = count($this->rows);

        // Header: tebal, latar hijau, teks putih, rata tengah vertikal
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0C5547']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Border tipis untuk seluruh area tabel data (header + baris)
        $tableLastRow = $dataRows + 1; // header di baris 1
        if ($dataRows > 0) {
            $sheet->getStyle("A1:{$lastCol}{$tableLastRow}")->applyFromArray([
                'borders' => ['allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'DADADA'],
                ]],
            ]);
            // Zebra baris genap
            for ($r = 2; $r <= $tableLastRow; $r++) {
                if ($r % 2 === 0) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F6F3F2');
                }
            }
        }

        // Blok ringkasan: label & nilai tebal
        if (! empty($this->summary)) {
            $summaryStart = $dataRows + 3; // +1 header, +1 baris kosong
            $sheet->getStyle("A{$summaryStart}:B{$lastRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$summaryStart}:A{$lastRow}")->getFont()->getColor()->setRGB('0C5547');
        }

        // Kunci baris header saat scroll
        $sheet->freezePane('A2');

        return [];
    }
}

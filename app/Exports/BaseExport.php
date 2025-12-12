<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class BaseExport
{
    /**
     * Apply common RTL styles to worksheet.
     */
    protected function applyCommonStyles(Worksheet $sheet, string $headerRange = null): void
    {
        $sheet->setRightToLeft(true);

        // Apply header styles if range is provided
        if ($headerRange) {
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
            ]);
        }

        // Apply common styles to entire sheet
        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
            'font' => [
                'name' => 'DejaVu Sans',
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
        ]);
    }
}


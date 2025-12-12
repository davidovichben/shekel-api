<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class BasePdfExport extends DefaultValueBinder implements WithCustomValueBinder, WithEvents
{
    /**
     * Apply common RTL styles for PDF exports.
     */
    protected function applyPdfStyles(Worksheet $sheet, string $headerRange): void
    {
        $sheet->setRightToLeft(true);

        // Apply header styles
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
        ]);

        // Apply common styles to entire sheet
        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
        ]);
    }

    /**
     * Register common PDF export events.
     */
    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function (BeforeExport $event) {
                $event->writer->getDelegate()->getProperties()->setCreator('Shekel');
            },
        ];
    }
}


<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class ExpensesPdfExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithStyles, WithCustomValueBinder, WithColumnWidths, WithEvents
{
    protected $expenses;

    public function __construct($expenses)
    {
        $this->expenses = $expenses;
    }

    public function collection()
    {
        return $this->expenses;
    }

    public function headings(): array
    {
        // Reversed order for RTL layout
        return [
            'תדירות',
            'סטטוס',
            'תאריך',
            'תיאור',
            'סכום',
            'סוג הוצאה',
            'שם ספק',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // תדירות
            'B' => 15,  // סטטוס
            'C' => 20,  // תאריך
            'D' => 40,  // תיאור
            'E' => 18,  // סכום
            'F' => 25,  // סוג הוצאה
            'G' => 30,  // שם ספק
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
        ]);

        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function (BeforeExport $event) {
                $event->writer->getDelegate()->getProperties()->setCreator('Shekel');
            },
        ];
    }
}


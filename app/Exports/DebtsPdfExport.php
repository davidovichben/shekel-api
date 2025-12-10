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

class DebtsPdfExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithStyles, WithCustomValueBinder, WithColumnWidths, WithEvents
{
    protected $debts;

    public function __construct($debts)
    {
        $this->debts = $debts;
    }

    public function collection()
    {
        return $this->debts;
    }

    public function headings(): array
    {
        // Reversed order for RTL layout
        return [
            'תאריך תזכורת אחרונה',
            'סטטוס',
            'תאריך יעד',
            'תיאור',
            'סכום',
            'סוג חוב',
            'שם מלא',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // תאריך תזכורת אחרונה
            'B' => 15,  // סטטוס
            'C' => 20,  // תאריך יעד
            'D' => 40,  // תיאור
            'E' => 18,  // סכום
            'F' => 20,  // סוג חוב
            'G' => 30,  // שם מלא
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


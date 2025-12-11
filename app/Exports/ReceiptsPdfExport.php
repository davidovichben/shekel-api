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

class ReceiptsPdfExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithStyles, WithCustomValueBinder, WithColumnWidths, WithEvents
{
    protected $receipts;

    public function __construct($receipts)
    {
        $this->receipts = $receipts;
    }

    public function collection()
    {
        return $this->receipts;
    }

    public function headings(): array
    {
        // Reversed order for RTL layout
        return [
            'סוג',
            'הערות',
            'תאריך',
            'אמצעי תשלום',
            'סטטוס',
            'סכום כולל',
            'משתמש',
            'מספר קבלה',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // סוג
            'B' => 40,  // הערות
            'C' => 20,  // תאריך
            'D' => 20,  // אמצעי תשלום
            'E' => 15,  // סטטוס
            'F' => 18,  // סכום כולל
            'G' => 25,  // משתמש
            'H' => 20,  // מספר קבלה
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);

        $sheet->getStyle('A1:H1')->applyFromArray([
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


<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReceiptsExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
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
        return [
            'מספר קבלה',
            'משתמש',
            'סכום כולל',
            'סטטוס',
            'אמצעי תשלום',
            'תאריך',
            'הערות',
            'סוג',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // מספר קבלה
            'B' => 25,  // משתמש
            'C' => 18,  // סכום כולל
            'D' => 15,  // סטטוס
            'E' => 20,  // אמצעי תשלום
            'F' => 20,  // תאריך
            'G' => 40,  // הערות
            'H' => 25,  // סוג
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);

        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
            'font' => [
                'name' => 'DejaVu Sans',
            ],
        ]);

        return [];
    }
}


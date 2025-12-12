<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReceiptsPdfExport extends BasePdfExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
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
        $this->applyPdfStyles($sheet, 'A1:H1');
        return [];
    }
}


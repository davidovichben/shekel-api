<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpensesPdfExport extends BasePdfExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
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
        $this->applyPdfStyles($sheet, 'A1:G1');
        return [];
    }
}


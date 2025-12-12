<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpensesExport extends BaseExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
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
        return [
            'שם ספק',
            'סוג הוצאה',
            'סכום',
            'תיאור',
            'תאריך',
            'סטטוס',
            'תדירות',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,  // שם ספק
            'B' => 25,  // סוג הוצאה
            'C' => 18,  // סכום
            'D' => 40,  // תיאור
            'E' => 20,  // תאריך
            'F' => 15,  // סטטוס
            'G' => 15,  // תדירות
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyCommonStyles($sheet);
        return [];
    }
}


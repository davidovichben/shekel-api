<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DebtsPdfExport extends BasePdfExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
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
        $this->applyPdfStyles($sheet, 'A1:G1');
        return [];
    }
}


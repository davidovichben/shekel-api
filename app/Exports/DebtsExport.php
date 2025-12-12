<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DebtsExport extends BaseExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
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
        return [
            'שם מלא',
            'סוג חוב',
            'סכום',
            'תיאור',
            'תאריך יעד',
            'סטטוס',
            'תאריך תזכורת אחרונה',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,  // שם מלא
            'B' => 20,  // סוג חוב
            'C' => 18,  // סכום
            'D' => 40,  // תיאור
            'E' => 20,  // תאריך יעד
            'F' => 15,  // סטטוס
            'G' => 25,  // תאריך תזכורת אחרונה
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyCommonStyles($sheet);
        return [];
    }
}


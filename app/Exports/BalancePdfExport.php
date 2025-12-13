<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalancePdfExport extends BasePdfExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $expenseTypeLabels = [
            'food' => 'מזון',
            'maintenance' => 'תחזוקת בית הכנסת',
            'equipment' => 'ציוד וריהוט',
            'insurance' => 'ביטוחים',
            'operations' => 'תפעול פעילויות',
            'suppliers' => 'ספקים ובעלי מקצוע',
            'management' => 'הנהלה ושכר',
        ];

        $expenseStatusLabels = [
            'paid' => 'שולם',
            'pending' => 'ממתין',
        ];

        $receiptTypeLabels = [
            'vows' => 'נדרים',
            'community_donations' => 'תרומות מהקהילה',
            'external_donations' => 'תרומות חיצוניות',
            'ascensions' => 'עליות',
            'online_donations' => 'תרומות אונליין',
            'membership_fees' => 'דמי חברים',
            'other' => 'אחר',
        ];

        $receiptStatusLabels = [
            'pending' => 'ממתין',
            'paid' => 'שולם',
            'cancelled' => 'בוטל',
            'refunded' => 'הוחזר',
        ];

        $rows = [];
        
        // Add summary row
        $rows[] = [
            'סיכום',
            '',
            '',
            '',
            $this->data['totalExpenses'],
            $this->data['totalIncome'],
            $this->data['balance'],
        ];
        
        // Add empty row
        $rows[] = ['', '', '', '', '', '', ''];
        
        // Add expenses section header
        $rows[] = [
            'הוצאות',
            '',
            '',
            '',
            '',
            '',
            '',
        ];
        
        // Add expense rows
        foreach ($this->data['expenses'] as $expense) {
            $rows[] = [
                $expense->description ?? '',
                $expense->date ? \Carbon\Carbon::parse($expense->date)->format('d/m/Y') : '',
                $expenseTypeLabels[$expense->type] ?? ($expense->type ?? ''),
                $expenseStatusLabels[$expense->status] ?? ($expense->status ?? ''),
                number_format((float)$expense->amount, 2, '.', ''),
                '',
                '',
            ];
        }
        
        // Add empty row
        $rows[] = ['', '', '', '', '', '', ''];
        
        // Add income section header
        $rows[] = [
            'הכנסות',
            '',
            '',
            '',
            '',
            '',
            '',
        ];
        
        // Add receipt rows
        foreach ($this->data['receipts'] as $receipt) {
            $rows[] = [
                $receipt->description ?? '',
                $receipt->date ? \Carbon\Carbon::parse($receipt->date)->format('d/m/Y') : '',
                $receiptTypeLabels[$receipt->type] ?? ($receipt->type ?? ''),
                $receiptStatusLabels[$receipt->status] ?? ($receipt->status ?? ''),
                '',
                number_format((float)$receipt->total, 2, '.', ''),
                '',
            ];
        }
        
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'תיאור',
            'תאריך',
            'סוג',
            'סטטוס',
            'הוצאות',
            'הכנסות',
            'מאזן',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,  // תיאור
            'B' => 20,  // תאריך
            'C' => 25,  // סוג
            'D' => 15,  // סטטוס
            'E' => 18,  // הוצאות
            'F' => 18,  // הכנסות
            'G' => 18,  // מאזן
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $this->applyPdfStyles($sheet, 'A1:G1');
        return [];
    }
}


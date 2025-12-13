<?php

namespace App\Exports;

use App\Exports\BaseExport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HashavshevetExport extends BaseExport implements FromCollection, WithHeadings, WithStyles
{
    protected $collection;
    protected $columns;
    protected $config;

    public function __construct($collection, array $columns, array $config)
    {
        $this->collection = $collection;
        $this->columns = $columns;
        $this->config = $config;
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        $headings = [];
        $columnLabels = collect($this->config['columns'])->keyBy('id')->map->label->toArray();
        
        foreach ($this->columns as $columnId) {
            $headings[] = $columnLabels[$columnId] ?? $columnId;
        }
        
        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRange = 'A1:' . $this->getColumnLetter(count($this->columns)) . '1';
        $this->applyCommonStyles($sheet, $headerRange);
        
        return [];
    }

    private function getColumnLetter($number)
    {
        $letter = '';
        while ($number > 0) {
            $number--;
            $letter = chr(65 + ($number % 26)) . $letter;
            $number = intval($number / 26);
        }
        return $letter;
    }
}


<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportDaybook implements FromCollection, WithHeadings
{
    protected $mergedData;

    public function __construct(Collection $mergedData)
    {
        $this->mergedData = $mergedData;
    }

    public function collection()
    {
        return $this->mergedData->map(function ($item) {
            return [
                "Invoice No" => $item['invoice_no'] ?? '',
                "Billing Name" => $item['billing_name'] ?? '',
                "Status" => $item['status'] ?? '',
                'Total Amount' => $item['total_amount'] ?? 0,
                'Reference No' => $item['Reference_no'] ?? '',
                'money_out' => $item['money_out'] ?? 0,
                'money_in' => $item['money_in'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return ['Invoice No', 'Billing Name', 'Status', 'Total Amount', 'Reference No', 'money_out', 'money_in'];
    }
}

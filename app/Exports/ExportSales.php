<?php


namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class ExportSales implements FromCollection, WithHeadings
{
    protected $sales;

    public function __construct(Collection $sales)
    {
        $this->sales = $sales;
    }

    public function collection()
    {
        return $this->sales->map(function ($sale) {
            return [
                "Invoice No" => $sale->invoice_no,
                "Billing Name" => $sale->billing_name,
                "Billing Address" => $sale->billing_address,
                "Sales Status" => $sale->sales_status,
                "Status" => $sale->status,
                'Date' => $sale->created_at->format('Y-m-d'),
                'Total Amount' => $sale->total_amount
            ];
        });
    }

    public function headings(): array
    {
        return ['Invoice No', 'Billing Name','Billing Address','Sales Status','Status','Date', 'Total Amount'];
    }
}

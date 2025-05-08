<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\Party;

class ExportPurchase implements  FromCollection, WithHeadings
{
   protected $purchases;
   
    public function __construct(Collection $purchases)
    {
        $this->purchases = $purchases;
    }
    public function collection()
{
    $firstPurchase = $this->purchases->first();
    $party = Party::find($firstPurchase->party_id);
    return $this->purchases->map(function ($purchase) use ($party) {
        return [
            "Invoice No" => $purchase->invoice_no,
            "Billing Name" => $party->party_name,
            "Purchase Status" => $purchase->sales_status,
            "Status" => $purchase->status,
            'Date' => $purchase->created_at->format('Y-m-d'),
            'Total Amount' => $purchase->total_amount
        ];
    });
}

    public function headings(): array
    {
        return ['Invoice No', 'Billing Name','Purchase Status','Status','Date', 'Total Amount'];
    }
}

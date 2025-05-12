<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class ExportAllTransaction implements FromCollection
{
    protected $mergedData;
    public function collection()
    {
        //    
    }
}

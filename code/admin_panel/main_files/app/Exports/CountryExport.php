<?php

namespace App\Exports;

use App\Models\Country;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CountryExport implements FromCollection, WithHeadings
{

    public function headings(): array
    {
        return [
            'Country Name'
        ];
    }

    public function collection()
    {
        return Country::select('name')->get();
    }
}

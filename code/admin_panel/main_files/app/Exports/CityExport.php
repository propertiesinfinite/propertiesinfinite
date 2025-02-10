<?php

namespace App\Exports;

use App\Models\City;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CityExport implements FromCollection, WithHeadings
{

    protected $is_dummy = false;

    public function __construct($is_dummy)
    {
        $this->is_dummy = $is_dummy;
    }

    public function headings(): array
    {
        return
            $this->is_dummy ? [
                'Country Id',
                'Name',
                'Slug'
            ] :
            [
                'Country Id',
                'Name',
                'Slug'
            ]
            ;
    }

    public function collection()
    {
        return $this->is_dummy ? City::select('country_id', 'name', 'slug')->get() : City::select('country_id', 'name', 'slug')->get();
    }
}

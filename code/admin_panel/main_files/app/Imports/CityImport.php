<?php

namespace App\Imports;

use App\Models\City;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Str;
class CityImport implements ToModel , WithStartRow
{

    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        return new City([
            'country_id' => $row[0],
            'name' => $row[1],
            'slug' => $row[2],
        ]);
    }
}

<?php

namespace App\Exports\Rh;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Rh\Sheets\GlobalSummarySheet;
use App\Exports\Rh\Sheets\DirectionSheet;
use App\Exports\Rh\Sheets\RhControlSheet;
use App\Models\Direction;

class AnnualRhExport implements WithMultipleSheets
{
    public function __construct(private int $annee) {}

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new GlobalSummarySheet($this->annee);

        foreach (Direction::orderBy('nom')->get() as $direction) {
            $sheets[] = new DirectionSheet($this->annee, $direction);
        }

        $sheets[] = new RhControlSheet($this->annee);

        return $sheets;
    }
}

<?php

namespace App\Exports\Reliquats;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReliquatsMultiSheetExport implements WithMultipleSheets
{
    protected $grouped;
    protected $annee;

    public function __construct($grouped, int $annee)
    {
        $this->grouped = $grouped;
        $this->annee   = $annee;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->grouped as $direction => $rows) {
            $sheets[] = new ReliquatsSheetExport(
                $direction ?: 'Sans direction',
                $rows,
                $this->annee
            );
        }

        return $sheets;
    }
}

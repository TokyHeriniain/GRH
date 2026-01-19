<?php

namespace App\Exports\Reliquats;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReliquatsSheetExport implements FromCollection, WithHeadings, WithTitle
{
    protected $direction;
    protected $rows;
    protected $annee;

    public function __construct(string $direction, $rows, int $annee)
    {
        $this->direction = $direction;
        $this->rows      = $rows;
        $this->annee     = $annee;
    }

    public function title(): string
    {
        return mb_substr($this->direction, 0, 31); // limite Excel
    }

    public function headings(): array
    {
        return [
            "Matricule",
            "Personnel",
            "Direction",
            "Service",
            "Reliquat {$this->annee}",
        ];
    }

    public function collection()
    {
        return $this->rows->map(fn ($r) => [
            $r['matricule'],
            $r['personnel'],
            $r['direction'],
            $r['service'],
            round($r['reliquat'], 2),
        ]);
    }
}

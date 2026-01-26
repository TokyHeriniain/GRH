<?php

namespace App\Exports\Reliquats;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReliquatsSheetExport implements FromCollection, WithHeadings, WithTitle
{
    protected Collection $rows;
    protected int $annee;
    protected string $title;

    public function __construct(Collection $rows, int $annee, string $title)
    {
        $this->rows  = $rows;
        $this->annee = $annee;
        $this->title = $title;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
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

    public function collection(): Collection
    {
        return $this->rows->map(fn ($r) => [
            $r['matricule'] ?? '',
            $r['personnel'] ?? '',
            $r['direction'] ?? '',
            $r['service'] ?? '',
            round((float) ($r['reliquat'] ?? 0), 2),
        ]);
    }
}

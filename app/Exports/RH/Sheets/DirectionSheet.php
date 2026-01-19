<?php

namespace App\Exports\Rh\Sheets;

use App\Models\Direction;
use App\Models\Leave;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithTitle,
    ShouldAutoSize,
    WithMapping,
    WithEvents
};
use Maatwebsite\Excel\Events\AfterSheet;

class DirectionSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected float $totalUtilises = 0;
    protected float $totalDroit = 0;
    protected float $totalSolde = 0;

    public function __construct(
        private int $annee,
        private Direction $direction
    ) {}

    public function title(): string
    {
        return mb_strimwidth($this->direction->nom, 0, 31);
    }

    public function collection()
    {
        $data = Leave::with(['personnel.direction', 'leaveType'])
            ->whereYear('date_debut', $this->annee)
            ->where('status', 'approuve_rh')
            ->whereHas('personnel', fn ($q) =>
                $q->where('direction_id', $this->direction->id)
            )
            ->get();

        $this->totalUtilises = $data->sum('jours_utilises');
        $this->totalDroit    = $data->sum('droit_total');
        $this->totalSolde    = $data->sum('solde_restant');

        return $data;
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Prenom',
            'Type de congé',
            'Jours utilisés',
            'Droit total',
            'Solde restant',
        ];
    }

    public function map($leave): array
    {
        return [
            $leave->personnel->matricule ?? '',
            $leave->personnel->nom ?? '',
            $leave->personnel->prenom ?? '',
            $leave->leaveType->libelle ?? '',
            round((float)$leave->jours_utilises, 2),
            round((float)$leave->droit_total, 2),
            round((float)$leave->solde_restant, 2),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $row = $event->sheet->getHighestRow() + 1;

                $event->sheet->setCellValue("A{$row}", 'TOTAL');
                $event->sheet->setCellValue("E{$row}", round($this->totalUtilises, 2));
                $event->sheet->setCellValue("F{$row}", round($this->totalDroit, 2));
                $event->sheet->setCellValue("G{$row}", round($this->totalSolde, 2));

                $event->sheet->getStyle("A{$row}:G{$row}")
                    ->getFont()->setBold(true);
            }
        ];
    }
}

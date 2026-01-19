<?php

namespace App\Exports\Rh\Sheets;

use App\Models\Leave;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithTitle,
    WithEvents
};
use Maatwebsite\Excel\Events\AfterSheet;

class GlobalSummarySheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithEvents
{
    protected float $totalUtilises = 0;
    protected float $totalDroit = 0;
    protected float $totalSolde = 0;

    public function __construct(private int $annee) {}

    public function title(): string
    {
        return 'Synthèse globale';
    }

    public function collection()
    {
        $data = Leave::with(['personnel.direction', 'leaveType'])
            ->whereYear('date_debut', $this->annee)
            ->where('status', 'approuve_rh')
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
            'Direction',
            'Type',
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
            $leave->personnel->direction->nom ?? '',
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
                $event->sheet->setCellValue("F{$row}", round($this->totalUtilises, 2));
                $event->sheet->setCellValue("G{$row}", round($this->totalDroit, 2));
                $event->sheet->setCellValue("H{$row}", round($this->totalSolde, 2));

                $event->sheet->getStyle("A{$row}:H{$row}")
                    ->getFont()->setBold(true);
            }
        ];
    }
}

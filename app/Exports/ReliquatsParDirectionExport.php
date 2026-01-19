<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\Models\Direction;

class ReliquatsParDirectionExport implements FromView
{
    private int $annee;

    public function __construct(int $annee)
    {
        $this->annee = $annee;
    }

    public function view(): View
    {
        $directions = Direction::with(['personnels.leaveBalance'])->get();

        return view('exports.reliquats-par-direction', [
            'annee' => $this->annee,
            'directions' => $directions,
        ]);
    }
}

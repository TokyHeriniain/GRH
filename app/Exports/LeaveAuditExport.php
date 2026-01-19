<?php

namespace App\Exports;

use App\Models\Leave;
use App\Models\LeaveAudit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;



class LeaveAuditExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return LeaveAudit::select(
            'action',
            'personnel_id',
            'actor_id',
            'created_at',
            'ip_address'
        )->get();
    }

    public function headings(): array
    {
        return [
            'Action',
            'Personnel',
            'Acteur RH',
            'Date',
            'IP'
        ];
    }
}

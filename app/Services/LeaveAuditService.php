<?php

namespace App\Services;

use App\Models\LeaveAudit;
use Illuminate\Http\Request;

class LeaveAuditService
{
    public function log(
        string $action,
        int $personnelId,
        ?int $leaveId = null,
        array $old = [],
        array $new = []
    ): void {
        LeaveAudit::create([
            'action'       => $action,
            'personnel_id' => $personnelId,
            'leave_id'     => $leaveId,
            'actor_id'     => auth()->id(),
            'old_values'   => $old,
            'new_values'   => $new,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }
}

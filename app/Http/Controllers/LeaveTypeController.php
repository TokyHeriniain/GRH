<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;

class LeaveTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(LeaveType::all());
    }
    
    public function search(Request $request)
    {
        $q = $request->query('q', '');

        $leaveTypes = LeaveType::when($q, function ($query, $q) {
            $query->where('nom', 'ilike', "%{$q}%");
        })
        ->groupBy('nom')
        ->orderBy('nom')
        ->get();

        return response()->json($leaveTypes);
    }
}

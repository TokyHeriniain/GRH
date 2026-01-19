<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index()
    {
        return response()->json([
            'total_users' => \App\Models\User::count(),
            'total_leaves' => \App\Models\Leave::count(),
            'approved_leaves' => \App\Models\Leave::where('status', 'approuvé')->count(),
        ]);
    }
}

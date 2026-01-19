<?php

// app/Http/Controllers/HolidayController.php
namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        return Holiday::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'date' => 'required|date|unique:holidays,date',
        ]);

        return Holiday::create($request->all());
    }

    public function update(Request $request, Holiday $holiday)
    {
        $request->validate([
            'title' => 'required|string',
            'date' => 'required|date|unique:holidays,date,' . $holiday->id,
        ]);

        $holiday->update($request->all());
        return $holiday;
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return response()->noContent();
    }
}

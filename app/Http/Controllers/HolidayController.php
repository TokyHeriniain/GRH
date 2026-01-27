<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('annee');

        $query = Holiday::query();

        if ($year) {
            $query->whereYear('date', $year);
        }

        return response()->json(
            $query->orderBy('date')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'date'  => 'required|date|unique:holidays,date',
        ]);

        return Holiday::create($data);
    }

    public function update(Request $request, Holiday $holiday)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'date'  => 'required|date|unique:holidays,date,' . $holiday->id,
        ]);

        $holiday->update($data);

        return $holiday;
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return response()->json(['success' => true]);
    }
}

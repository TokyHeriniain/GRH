<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use Illuminate\Http\Request;

class DirectionController extends Controller
{
    public function index()
    {
        return Direction::with('services.fonctions')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
        ]);
        return Direction::create($request->only('nom'));
    }

    public function update(Request $request, Direction $direction)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
        ]);
        $direction->update($request->only('nom'));
        return $direction;
    }

    public function destroy(Direction $direction)
    {
        $direction->delete();
        return response()->noContent();
    }

    public function arborescence()
    {
        $data = Direction::with([
            'services' => function ($q) {
                $q->with('fonctions');
            }
        ])->get();

        return response()->json($data);
    }
}

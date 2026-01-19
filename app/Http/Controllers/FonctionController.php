<?php

namespace App\Http\Controllers;

use App\Models\Fonction;
use Illuminate\Http\Request;

class FonctionController extends Controller
{
    public function index()
    {
        return Fonction::with('service.direction')->get();
    }


    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'service_id' => 'required|exists:services,id',
        ]);
        return Fonction::create($request->only('nom', 'service_id'));
    }

    public function update(Request $request, Fonction $fonction)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'service_id' => 'required|exists:services,id',
        ]);
        $fonction->update($request->only('nom', 'service_id'));
        return $fonction;
    }

    public function destroy(Fonction $fonction)
    {
        $fonction->delete();
        return response()->noContent();
    }
}


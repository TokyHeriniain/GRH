<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return Service::with('direction')->get();
    }


    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'direction_id' => 'required|exists:directions,id',
        ]);
        return Service::create($request->only('nom', 'direction_id'));
    }

    public function update(Request $request, Service $service)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'direction_id' => 'required|exists:directions,id',
        ]);
        $service->update($request->only('nom', 'direction_id'));
        return $service;
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return response()->noContent();
    }
}


<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use App\Models\Service;
use App\Models\Fonction;
use Illuminate\Http\Request;

class StructureController extends Controller
{
    public function directions()
    {
        return Direction::with('services.fonctions')->get();
    }

    public function servicesByDirection($directionId)
    {
        return Service::where('direction_id', $directionId)->with('fonctions')->get();
    }

    public function fonctionsByService($serviceId)
    {
        return Fonction::where('service_id', $serviceId)->get();
    }

    public function all()
    {
        return response()->json([
            'directions' => Direction::all(),
            'services' => Service::all(),
            'fonctions' => Fonction::all(),
        ]);
    }
}


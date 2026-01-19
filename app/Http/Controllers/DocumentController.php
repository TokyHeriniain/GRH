<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Personnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    // Liste documents d’un personnel
    public function index($personnelId)
    {
        $personnel = Personnel::findOrFail($personnelId);
        return response()->json($personnel->documents);
    }

    // Upload document pour un personnel
   public function store(Request $request, Personnel $personnel)
    {
        $request->validate([
            'nom' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'fichier' => 'required|file|max:5120',
        ]);

        $path = $request->file('fichier')->store('documents', 'public');

        $document = $personnel->documents()->create([
            'nom' => $request->input('nom'),
            'type' => $request->input('type'),
            'fichier' => $path,
        ]);

        return response()->json($document, 201);
    }


    // Supprimer un document
    public function destroy($id)
    {
        $document = Document::findOrFail($id);

        if (Storage::disk('public')->exists($document->fichier)) {
            Storage::disk('public')->delete($document->fichier);
        }

        $document->delete();

        return response()->json(['message' => 'Document supprimé']);
    }

    public function getByPersonnel($id)
    {
        $documents = Document::where('personnel_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'nom' => $doc->nom,
                    'type' => $doc->type,
                    'created_at' => $doc->created_at,
                    'url' => asset('storage/documents/' . $doc->fichier),
                ];
            });

        return response()->json($documents);
    }

}


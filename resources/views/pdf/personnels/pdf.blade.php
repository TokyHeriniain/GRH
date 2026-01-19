<!-- resources/views/personnels/pdf.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des personnels</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px; text-align: left; }
    </style>
</head>
<body>
    <h2>Liste des personnels</h2>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Fonction</th>
                <th>Matricule</th>
                <th>Date entrée</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($personnels as $pers)
                <tr>
                    <td>{{ $pers->nom }}</td>
                    <td>{{ $pers->prenom }}</td>
                    <td>{{ $pers->fonction }}</td>
                    <td>{{ $pers->matricule }}</td>
                    <td>{{ $pers->date_entree }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

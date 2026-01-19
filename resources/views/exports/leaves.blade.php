<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export Congés</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { display: flex; align-items: center; margin-bottom: 20px; }
        .logo { width: 80px; margin-right: 20px; }
        .title { text-align: center; flex-grow: 1; }
        h1 { margin: 0; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 5px; text-align: center; }
        th { background-color: #eee; }
        .footer { text-align: right; font-size: 10px; margin-top: 20px; }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ public_path('images/logo.png') }}" class="logo">
    <div class="title">
        <h1>Compagnie d'Assurance et de Réassurance Ny Havana</h1>
        <p>Liste des congés - Exporté le {{ $dateExport }}</p>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Nom</th>
            <th>Fonction</th>
            <th>Type</th>
            <th>Période</th>
            <th>Raison</th>
            <th>Jours</th>
            <th>Droit</th>
            <th>Solde</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($leaves as $leave)
        <tr>
            <td>{{ $leave->personnel->nom }} {{ $leave->personnel->prenom }}</td>
            <td>{{ $leave->personnel->fonction->nom ?? '-' }}</td>
            <td>{{ $leave->leaveType->nom }}</td>
            <td>{{ $leave->date_debut }} {{ $leave->heure_debut }} → {{ $leave->date_fin }} {{ $leave->heure_fin }}</td>
            <td>{{ $leave->raison }}</td>
            <td>{{ number_format($leave->jours_utilises, 2, ',', ' ') }}</td>
            <td>{{ number_format($leave->droit_total, 2, ',', ' ') }}</td>
            <td>{{ number_format($leave->solde_restant, 2, ',', ' ') }}</td>
            <td>{{ ucfirst($leave->status) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    Généré automatiquement par le système de gestion des congés.
</div>

</body>
</html>

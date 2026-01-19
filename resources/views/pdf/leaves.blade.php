<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export Congés</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 20px; }
        .header { display: flex; align-items: center; border-bottom: 2px solid #444; padding-bottom: 10px; margin-bottom: 15px; }
        .logo { width: 80px; height: auto; margin-right: 15px; }
        .title { flex: 1; text-align: center; }
        h1 { margin: 0; font-size: 18px; }
        p { margin: 3px 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 6px; text-align: center; }
        th { background-color: #eee; }
        .footer { text-align: right; font-size: 10px; margin-top: 15px; color: #555; }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ public_path('images/logo1-02.png') }}" class="logo">
    <div class="title">
        <h1>Compagnie d'Assurance et de Réassurance Ny Havana</h1>
        <p>Liste des congés — Exporté le {{ $dateExport }}</p>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Matricule</th>
            <th>Nom</th>
            <th>Direction</th>
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
            <td>{{ $leave->personnel->matricule ?? '-' }}</td>
            <td>{{ $leave->personnel->nom }} {{ $leave->personnel->prenom }}</td>
            <td>{{ optional($leave->personnel->direction)->nom ?? '-' }}</td>
            <td>{{ $leave->leaveType->nom }}</td>
            <td>{{ $leave->date_debut }} {{ $leave->heure_debut }} → {{ $leave->date_fin }} {{ $leave->heure_fin }}</td>
            <td>{{ $leave->raison ?? '-' }}</td>
            <td>{{ number_format($leave->jours_utilises, 2, ',', ' ') }}</td>
            <td>{{ $leave->droit_total !== null ? number_format($leave->droit_total, 2, ',', ' ') : '-' }}</td>
            <td>{{ $leave->solde_restant !== null ? number_format($leave->solde_restant, 2, ',', ' ') : '-' }}</td>
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

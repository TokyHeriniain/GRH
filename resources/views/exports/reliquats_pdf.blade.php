<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; }
        h2, h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; }
        .signature { margin-top: 50px; text-align: right; }
    </style>
</head>
<body>

<h2>LISTE DES RELIQUATS DE CONGÉS</h2>
<h3>Avant clôture annuelle</h3>
<p>Date : {{ $date }}</p>

@foreach($directions as $direction => $rows)
    <h4>Direction : {{ $direction }}</h4>

    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Reliquat (jours)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r->matricule }}</td>
                    <td>{{ $r->nom }}</td>
                    <td>{{ $r->prenom }}</td>
                    <td style="text-align:right">{{ number_format($r->solde_global_jours, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

<div class="signature">
    <p>Responsable RH</p>
    <br><br>
    <p>__________________________</p>
    <p><em>Cachet et signature</em></p>
</div>

</body>
</html>

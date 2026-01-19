<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans; font-size: 11px; }
        table { width:100%; border-collapse: collapse; margin-top:10px; }
        th, td { border:1px solid #000; padding:4px; }
        th { background:#f0f0f0; }
        .right { text-align:right; }
        .center { text-align:center; }
    </style>
</head>
<body>

<h3 class="center">RELIQUAT DES CONGES</h3>

<p>
    Année : <strong>{{ $annee }}</strong><br>
    Établi le : {{ now()->format('d/m/Y H:i') }}
</p>

<table>
    <thead>
        <tr>
            <th>Matricule</th>
            <th>Personnel</th>
            <th>Solde {{ $annee }}</th>
            <th>Report</th>
            <th>Perte</th>
            <th>Solde {{ $annee + 1 }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $r)
        <tr>
            <td>{{ $r['Matricule'] }}</td>
            <td>{{ $r['Personnel'] }}</td>
            <td class="right">{{ $r['Solde'] }}</td>
            <td class="right">{{ $r['Report'] }}</td>
            <td class="right">{{ $r['Perte'] }}</td>
            <td class="right">{{ $r['Solde_N_plus_1'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<br><br>

<p>
    Validé par : <strong>RH</strong><br>
    Date : <strong>{{ $r['Valide_le'] }}</strong>
</p>

<p style="margin-top:40px;">
    Signature RH : ___________________________
</p>

</body>
</html>
